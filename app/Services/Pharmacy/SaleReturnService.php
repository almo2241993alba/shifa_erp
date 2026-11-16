<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\InsuranceClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnPayment;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function create(User $user, array $data): SaleReturn
    {
        return DB::transaction(function () use ($user, $data): SaleReturn {
            $session = PosSession::query()->lockForUpdate()->findOrFail($data['pos_session_id']);
            if ($session->status !== 'open' || $session->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'An open POS session owned by the current user is required.',
                ]);
            }

            $sale = Sale::query()->lockForUpdate()->with('insuranceClaim')
                ->whereKey($data['sale_id'])
                ->where('branch_id', $session->branch_id)
                ->firstOrFail();
            $treasury = Treasury::query()
                ->whereKey($session->treasury_id)
                ->where('branch_id', $session->branch_id)
                ->where('type', 'cash_drawer')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            if ($treasury->id !== (int) $data['treasury_id']) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'The refund must be paid from the current POS session treasury.',
                ]);
            }

            $refundItems = $this->prepareReturnItems($sale, $data['items']);
            $patientRefund = round(array_sum(array_column($refundItems, 'patient_refund')), 4);
            $insuranceRefund = round(array_sum(array_column($refundItems, 'insurance_refund')), 4);
            $taxRefund = round(array_sum(array_column($refundItems, 'tax_refund')), 4);
            $grossRefund = round(array_sum(array_column($refundItems, 'gross_refund')), 4);
            $refundPayments = $this->allocatePatientRefund($sale, $session, $patientRefund);
            $refundTreasuries = $this->lockRefundTreasuries($refundPayments, $session, $treasury);
            $this->assertRefundBalances($refundPayments, $refundTreasuries);

            $claim = $sale->insuranceClaim;
            if ($insuranceRefund > 0) {
                if ($claim === null || $claim->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'sale_id' => 'An insured return is allowed only while its claim is pending.',
                    ]);
                }
                if ($insuranceRefund > (float) $claim->claimed_amount) {
                    throw ValidationException::withMessages([
                        'items' => 'The insurance refund exceeds the remaining claim amount.',
                    ]);
                }
            }

            $return = SaleReturn::query()->create([
                'financial_transaction_id' => FinancialTransaction::query()->create()->id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'pos_session_id' => $session->id,
                'user_id' => $user->id,
                'treasury_id' => $treasury->id,
                'return_number' => 'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'total_refund_amount' => $grossRefund,
                'patient_refund_amount' => $patientRefund,
                'insurance_refund_amount' => $insuranceRefund,
                'tax_refund_amount' => $taxRefund,
                'reason' => $data['reason'],
            ]);

            foreach ($refundPayments as $refundPayment) {
                SaleReturnPayment::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_payment_id' => $refundPayment['sale_payment']->id,
                    'treasury_id' => $refundPayment['treasury_id'],
                    'amount' => $refundPayment['amount'],
                ]);
            }

            $costReturned = 0.0;
            foreach ($refundItems as $item) {
                $item['batch']->increment('remaining_quantity_base', $item['base_quantity']);
                SaleReturnItem::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $item['sale_item']->id,
                    'medicine_id' => $item['sale_item']->medicine_id,
                    'unit_id' => $item['sale_item']->unit_id,
                    'batch_id' => $item['batch']->id,
                    'returned_quantity' => $item['quantity'],
                    'base_quantity_restored' => $item['base_quantity'],
                    'unit_price_at_sale' => $item['sale_item']->unit_price,
                    'tax_refund_amount' => $item['tax_refund'],
                    'line_total' => $item['gross_refund'],
                ]);
                $costReturned += (float) $item['sale_item']->landed_cost_at_sale * $item['base_quantity'];
            }

            if ($insuranceRefund > 0 && $claim !== null) {
                $remainingClaim = round((float) $claim->claimed_amount - $insuranceRefund, 4);
                $claim->forceFill([
                    'claimed_amount' => $remainingClaim,
                    'status' => $remainingClaim === 0.0 ? 'rejected' : 'pending',
                    'approved_amount' => $remainingClaim === 0.0 ? 0 : null,
                    'processed_by' => $remainingClaim === 0.0 ? $user->id : null,
                    'rejection_reason' => $remainingClaim === 0.0 ? 'Claim cancelled by a full sale return.' : null,
                    'decided_at' => $remainingClaim === 0.0 ? now() : null,
                ])->save();
                $sale->decrement('insurance_due', $insuranceRefund);
            }

            $cashRefund = 0.0;
            foreach ($refundPayments as $refundPayment) {
                $refundTreasuries[$refundPayment['treasury_id']]->decrement('current_balance', $refundPayment['amount']);
                if ($refundPayment['sale_payment']->payment_method === 'cash') {
                    $cashRefund += $refundPayment['amount'];
                }
            }
            if ($cashRefund > 0) {
                $session->decrement('expected_cash', $cashRefund);
            }

            $this->postJournal($return, $refundPayments, $refundTreasuries, $claim, $costReturned, $taxRefund, $user);
            $soldQuantity = (int) SaleItem::query()->where('sale_id', $sale->id)->sum('quantity');
            $returnedQuantity = (int) $sale->returns()->with('items')->get()->sum(
                fn (SaleReturn $saleReturn) => $saleReturn->items->sum('returned_quantity'),
            );
            $sale->forceFill([
                'status' => $returnedQuantity >= $soldQuantity ? 'returned' : 'partially_returned',
            ])->save();

            return $return->load([
                'items.medicine',
                'items.batch',
                'sale',
                'posSession',
                'treasury',
                'payments.salePayment',
                'payments.treasury',
            ]);
        });
    }

    private function allocatePatientRefund(Sale $sale, PosSession $session, float $patientRefund): array
    {
        if ($patientRefund <= 0) {
            return [];
        }

        $payments = $sale->payments()
            ->whereIn('payment_method', ['cash', 'card'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $availablePayments = [];
        $totalAvailable = 0.0;

        foreach ($payments as $payment) {
            $available = round((float) $payment->amount - (float) $payment->returnPayments()->sum('amount'), 4);
            if ($available > 0) {
                $availablePayments[] = ['sale_payment' => $payment, 'available' => $available];
                $totalAvailable = round($totalAvailable + $available, 4);
            }
        }

        if ($patientRefund > $totalAvailable) {
            throw ValidationException::withMessages([
                'items' => 'A refund cannot exceed the original cash and card payments that have not already been refunded.',
            ]);
        }

        $remainingRefund = $patientRefund;
        $remainingAvailable = $totalAvailable;
        $allocations = [];

        foreach ($availablePayments as $index => $availablePayment) {
            $payment = $availablePayment['sale_payment'];
            $available = $availablePayment['available'];
            $amount = $index === array_key_last($availablePayments)
                ? min($available, $remainingRefund)
                : min($available, round($remainingRefund * $available / $remainingAvailable, 4));
            $remainingAvailable = round($remainingAvailable - $available, 4);
            if ($amount <= 0) {
                continue;
            }
            if ($payment->payment_method === 'card' && $payment->treasury_id === null) {
                throw ValidationException::withMessages([
                    'sale_id' => 'The original card payment has no recorded bank treasury.',
                ]);
            }

            $allocations[] = [
                'sale_payment' => $payment,
                'treasury_id' => $payment->payment_method === 'cash'
                    ? $session->treasury_id
                    : (int) $payment->treasury_id,
                'amount' => $amount,
            ];
            $remainingRefund = round($remainingRefund - $amount, 4);
        }

        if ($remainingRefund > 0) {
            throw ValidationException::withMessages([
                'items' => 'The refund could not be allocated across the original payment methods.',
            ]);
        }

        return $allocations;
    }

    private function lockRefundTreasuries(array $refundPayments, PosSession $session, Treasury $drawer): array
    {
        $treasuries = [$drawer->id => $drawer];
        $cardTreasuryIds = collect($refundPayments)
            ->filter(fn (array $payment) => $payment['sale_payment']->payment_method === 'card')
            ->pluck('treasury_id')
            ->unique()
            ->sort()
            ->values();

        foreach ($cardTreasuryIds as $treasuryId) {
            $treasuries[$treasuryId] = Treasury::query()
                ->whereKey($treasuryId)
                ->where('branch_id', $session->branch_id)
                ->where('type', 'bank')
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $treasuries;
    }

    private function assertRefundBalances(array $refundPayments, array $treasuries): void
    {
        $refundsByTreasury = [];
        foreach ($refundPayments as $refundPayment) {
            $treasuryId = $refundPayment['treasury_id'];
            $refundsByTreasury[$treasuryId] = round(
                ($refundsByTreasury[$treasuryId] ?? 0) + $refundPayment['amount'],
                4,
            );
        }

        foreach ($refundsByTreasury as $treasuryId => $amount) {
            if ((float) $treasuries[$treasuryId]->current_balance < $amount) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'The refund treasury does not have enough balance for this refund.',
                ]);
            }
        }
    }

    private function prepareReturnItems(Sale $sale, array $requestedItems): array
    {
        $results = [];
        $seenSaleItems = [];
        foreach ($requestedItems as $index => $requested) {
            if (in_array($requested['sale_item_id'], $seenSaleItems, true)) {
                throw ValidationException::withMessages([
                    "items.$index.sale_item_id" => 'Each sale line can appear only once in a return.',
                ]);
            }
            $seenSaleItems[] = $requested['sale_item_id'];

            $saleItem = SaleItem::query()->lockForUpdate()
                ->where('sale_id', $sale->id)
                ->findOrFail($requested['sale_item_id']);
            $quantity = (int) $requested['quantity'];
            $previouslyReturned = (int) $saleItem->returnItems()->sum('returned_quantity');
            if ($quantity < 1 || $previouslyReturned + $quantity > (int) $saleItem->quantity) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'The requested quantity exceeds the unreturned sale quantity.',
                ]);
            }

            $ratio = $quantity / (int) $saleItem->quantity;
            $baseQuantity = intdiv((int) $saleItem->base_quantity_deducted * $quantity, (int) $saleItem->quantity);
            $patientRefund = round((float) $saleItem->patient_amount * $ratio, 4);
            $insuranceRefund = round((float) $saleItem->insurance_covered_amount * $ratio, 4);
            $results[] = [
                'sale_item' => $saleItem,
                'batch' => $saleItem->batch()->lockForUpdate()->firstOrFail(),
                'quantity' => $quantity,
                'base_quantity' => $baseQuantity,
                'gross_refund' => round($patientRefund + $insuranceRefund, 4),
                'patient_refund' => $patientRefund,
                'insurance_refund' => $insuranceRefund,
                'tax_refund' => round((float) $saleItem->tax_amount * $ratio, 4),
            ];
        }

        return $results;
    }

    private function postJournal(
        SaleReturn $return,
        array $refundPayments,
        array $refundTreasuries,
        ?InsuranceClaim $claim,
        float $costReturned,
        float $taxRefund,
        User $user,
    ): void {
        $settings = Setting::query()->firstOrFail();
        $lines = [];
        $netReturn = round((float) $return->total_refund_amount - $taxRefund, 4);
        if ($netReturn > 0) {
            if ($settings->sales_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the sales account before processing returns.',
                ]);
            }
            $this->addLine($lines, $settings->sales_account_id, $netReturn, 0, 'Sales return');
        }
        if ($taxRefund > 0) {
            if ($settings->vat_payable_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the VAT payable account before processing returns.',
                ]);
            }
            $this->addLine($lines, $settings->vat_payable_account_id, $taxRefund, 0, 'Output VAT reversal');
        }
        foreach ($refundPayments as $refundPayment) {
            $treasury = $refundTreasuries[$refundPayment['treasury_id']];
            $method = $refundPayment['sale_payment']->payment_method;
            $this->addLine(
                $lines,
                $treasury->account_id,
                0,
                $refundPayment['amount'],
                $method === 'cash' ? 'Patient cash refund' : 'Patient card refund',
            );
        }
        if ((float) $return->insurance_refund_amount > 0 && $claim !== null) {
            $insurer = Contact::query()->findOrFail($claim->insurer_contact_id);
            $receivable = $insurer->customer_account_id
                ?? Account::query()->where('code', '1301')->firstOrFail()->id;
            $this->addLine($lines, (int) $receivable, 0, (float) $return->insurance_refund_amount, 'Insurance claim adjustment');
        }
        if ($costReturned > 0) {
            if ($settings->inventory_account_id === null || $settings->cogs_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure inventory and cost-of-goods accounts before processing returns.',
                ]);
            }
            $this->addLine($lines, $settings->inventory_account_id, $costReturned, 0, 'Returned inventory');
            $this->addLine($lines, $settings->cogs_account_id, 0, $costReturned, 'Cost of returned goods');
        }

        $debit = round(array_sum(array_column($lines, 'debit')), 4);
        $credit = round(array_sum(array_column($lines, 'credit')), 4);
        if ($debit !== $credit) {
            throw ValidationException::withMessages([
                'accounting' => 'The return could not be posted because its journal entry is not balanced.',
            ]);
        }

        if ($lines === []) {
            return;
        }

        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $return->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => today(),
            'description' => 'Automatic posting for sale return '.$return->return_number,
            'total_debit' => $debit,
            'total_credit' => $credit,
            'is_auto_generated' => true,
        ]);
        foreach ($lines as $line) {
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                ...$line,
            ]);
        }
    }

    private function addLine(array &$lines, int $accountId, float $debit, float $credit, string $memo): void
    {
        if (max($debit, $credit) > 0) {
            $lines[] = [
                'account_id' => $accountId,
                'debit' => round($debit, 4),
                'credit' => round($credit, 4),
                'memo' => $memo,
            ];
        }
    }
}
