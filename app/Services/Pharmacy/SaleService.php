<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\Batch;
use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private SalePricingService $pricing) {}

    public function quote(User $user, array $data): array
    {
        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : $user->branch_id;
        if ($user->branch_id !== null && $branchId !== (int) $user->branch_id) {
            abort(403, 'You cannot prepare a sale for another branch.');
        }
        if ($branchId === null && $user->role !== 'admin') {
            abort(403, 'Assign a branch before preparing a sale.');
        }

        $customer = $this->customer($data['customer_id'] ?? null, $user, $branchId);
        $policy = $this->policy($data['insurance_policy_id'] ?? null, $customer);
        $setting = Setting::query()->firstOrFail();
        $plan = $policy?->plan()->with('medicineCoverages')->first();
        $planRules = $plan?->medicineCoverages->keyBy('medicine_id') ?? collect();
        $pricingItems = $this->pricingItems($data['items'], $plan, $planRules);
        $invoiceDiscount = (float) ($data['invoice_discount'] ?? 0);

        if ($invoiceDiscount > 0 && blank($data['discount_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'discount_reason' => 'A reason is required for an invoice discount.',
            ]);
        }

        return $this->pricing->price(
            $pricingItems,
            $invoiceDiscount,
            (float) $setting->tax_rate,
            (float) $setting->discount_approval_limit,
            $user->hasRole('admin'),
            $plan?->max_coverage_per_claim === null ? null : (float) $plan->max_coverage_per_claim,
        );
    }

    public function create(User $user, array $data): Sale
    {
        return DB::transaction(function () use ($user, $data): Sale {
            $session = PosSession::query()->lockForUpdate()->findOrFail($data['pos_session_id']);

            if ($session->status !== 'open' || $session->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'The sale requires an open session owned by the current user.',
                ]);
            }

            if ($user->branch_id !== null && (int) $session->branch_id !== (int) $user->branch_id) {
                abort(403, 'The POS session belongs to another branch.');
            }

            $customer = $this->customer($data['customer_id'] ?? null, $user, (int) $session->branch_id);
            $policy = $this->policy($data['insurance_policy_id'] ?? null, $customer);
            $setting = Setting::query()->firstOrFail();
            $plan = $policy?->plan()->with('medicineCoverages')->first();
            $planRules = $plan?->medicineCoverages->keyBy('medicine_id') ?? collect();
            $pricingItems = $this->pricingItems($data['items'], $plan, $planRules);

            $invoiceDiscount = (float) ($data['invoice_discount'] ?? 0);
            if ($invoiceDiscount > 0 && blank($data['discount_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'discount_reason' => 'A reason is required for an invoice discount.',
                ]);
            }

            $calculation = $this->pricing->price(
                $pricingItems,
                $invoiceDiscount,
                (float) $setting->tax_rate,
                (float) $setting->discount_approval_limit,
                $user->hasRole('admin'),
                $plan?->max_coverage_per_claim === null ? null : (float) $plan->max_coverage_per_claim,
            );

            $financialTransaction = FinancialTransaction::query()->create();
            $sale = Sale::query()->create([
                'financial_transaction_id' => $financialTransaction->id,
                'branch_id' => $session->branch_id,
                'pos_session_id' => $session->id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'insurance_policy_id' => $policy?->id,
                'treasury_id' => $session->treasury_id,
                'invoice_number' => $this->invoiceNumber(),
                'session_receipt_number' => (int) Sale::query()
                    ->where('pos_session_id', $session->id)
                    ->max('session_receipt_number') + 1,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'discount_reason' => $data['discount_reason'] ?? null,
                'discounted_by_user_id' => $calculation['discount_amount'] > 0 ? $user->id : null,
                'discount_approved_by_user_id' => $calculation['requires_approval'] ? $user->id : null,
                'tax_amount' => $calculation['tax_amount'],
                'grand_total' => $calculation['grand_total'],
                'patient_due' => $calculation['patient_due'],
                'insurance_due' => $calculation['insurance_due'],
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_method' => 'credit',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $costOfGoods = $this->createSaleItems($sale, $calculation['items'], $session->branch_id, $user);
            $payments = $this->payments($sale, $session, $customer, $calculation['patient_due'], $data['payments'] ?? []);

            $sale->forceFill([
                'paid_amount' => $payments['paid_amount'],
                'change_amount' => $payments['change_amount'],
                'payment_method' => $payments['payment_method'],
            ])->save();

            if ($calculation['insurance_due'] > 0) {
                if ($policy === null || $customer === null || $plan === null) {
                    throw ValidationException::withMessages([
                        'insurance_policy_id' => 'An active policy and patient are required for an insurance balance.',
                    ]);
                }

                InsuranceClaim::query()->create([
                    'sale_id' => $sale->id,
                    'insurance_policy_id' => $policy->id,
                    'insurer_contact_id' => $plan->insurer_contact_id,
                    'patient_contact_id' => $customer->id,
                    'submitted_by' => $user->id,
                    'claim_number' => $this->claimNumber(),
                    'claimed_amount' => $calculation['insurance_due'],
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);
            }

            $this->postJournal($sale, $session, $customer, $plan?->insurer_contact_id, $payments, $costOfGoods, $setting, $user);
            $session->increment('expected_cash', $payments['cash_received']);

            foreach ($payments['settlements'] as $settlement) {
                Treasury::query()->whereKey($settlement['treasury_id'])->increment('current_balance', $settlement['amount']);
            }

            return $sale->load([
                'items.medicine',
                'items.unit',
                'items.batch',
                'payments',
                'insurancePolicy.plan.insurer',
                'insuranceClaim',
            ]);
        });
    }

    private function customer(?int $customerId, User $user, ?int $branchId): ?Contact
    {
        if ($customerId === null) {
            return null;
        }

        $customer = Contact::query()->findOrFail($customerId);

        if (! $customer->is_active || ! $customer->is_customer) {
            throw ValidationException::withMessages([
                'customer_id' => 'The selected contact is not an active customer.',
            ]);
        }

        if ($user->branch_id === null && $user->role !== 'admin') {
            abort(403, 'Assign a branch before selecting a sale customer.');
        }
        if ($branchId !== null && (int) $customer->branch_id !== $branchId) {
            abort(403, 'The selected customer belongs to another branch.');
        }

        return $customer;
    }

    private function policy(?int $policyId, ?Contact $customer): ?InsurancePolicy
    {
        if ($policyId === null) {
            return null;
        }

        if ($customer === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'An insured sale must be linked to a patient.',
            ]);
        }

        $policy = InsurancePolicy::query()->with('plan')
            ->whereKey($policyId)
            ->where('patient_contact_id', $customer->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $policy->plan->is_active
            || $policy->valid_from->isAfter(today())
            || ($policy->valid_until !== null && $policy->valid_until->isBefore(today()))) {
            throw ValidationException::withMessages([
                'insurance_policy_id' => 'The insurance policy is inactive or outside its validity dates.',
            ]);
        }

        return $policy;
    }

    private function pricingItems(array $requestedItems, $plan, $planRules): array
    {
        $items = [];

        foreach ($requestedItems as $index => $requested) {
            $medicine = Medicine::query()->findOrFail($requested['medicine_id']);
            if (! $medicine->is_active) {
                throw ValidationException::withMessages([
                    "items.$index.medicine_id" => 'Inactive medicines cannot be sold.',
                ]);
            }

            $medicineUnit = MedicineUnit::query()
                ->where('medicine_id', $medicine->id)
                ->where('unit_id', $requested['unit_id'])
                ->where('is_sellable', true)
                ->first();

            if ($medicineUnit === null || $medicineUnit->conversion_factor < 1) {
                throw ValidationException::withMessages([
                    "items.$index.unit_id" => 'The selected unit is not configured for sale.',
                ]);
            }

            $rule = $planRules->get($medicine->id);
            $isCovered = $plan !== null && ($rule === null || $rule->is_covered);
            $items[] = [
                'medicine_id' => $medicine->id,
                'unit_id' => $medicineUnit->unit_id,
                'quantity' => (int) $requested['quantity'],
                'conversion_factor' => (int) $medicineUnit->conversion_factor,
                'unit_price' => (float) $medicineUnit->selling_price,
                'discount_amount' => (float) ($requested['discount_amount'] ?? 0),
                'discount_reason' => $requested['discount_reason'] ?? null,
                'free_source' => $requested['free_source'] ?? 'none',
                'is_taxable' => $medicine->is_taxable,
                'coverage_percentage' => $isCovered
                    ? (float) ($rule?->coverage_percentage ?? $plan->coverage_percentage)
                    : 0,
                'patient_copay_percentage' => $isCovered
                    ? (float) ($rule?->patient_copay_percentage ?? $plan->patient_copay_percentage)
                    : 0,
                'max_coverage_amount' => $rule?->max_coverage_amount,
                'is_insurance_covered' => $isCovered,
                'dispensing_method' => $medicine->dispensing_method,
            ];
        }

        return $items;
    }

    private function createSaleItems(Sale $sale, array $items, int $branchId, User $user): float
    {
        $costOfGoods = 0.0;

        foreach ($items as $item) {
            $allocations = $this->allocateStock($item, $branchId);
            $remaining = [
                'discount_amount' => (float) $item['discount_amount'],
                'tax_amount' => (float) $item['tax_amount'],
                'line_total' => (float) $item['line_total'],
                'insurance_covered_amount' => (float) $item['insurance_covered_amount'],
                'patient_amount' => (float) $item['patient_amount'],
            ];
            $allocatedUnits = 0;

            foreach ($allocations as $index => $allocation) {
                $quantity = $allocation['quantity'];
                $allocatedUnits += $quantity;
                $values = [];
                foreach ($remaining as $field => $amount) {
                    $part = $index === array_key_last($allocations)
                        ? $amount
                        : round($amount * $quantity / $item['quantity'], 4);
                    $values[$field] = $part;
                    $remaining[$field] = round($remaining[$field] - $part, 4);
                }

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'medicine_id' => $item['medicine_id'],
                    'unit_id' => $item['unit_id'],
                    'batch_id' => $allocation['batch']->id,
                    'quantity' => $quantity,
                    'base_quantity_deducted' => $quantity * $item['conversion_factor'],
                    'unit_price' => $item['unit_price'],
                    ...$values,
                    'discount_reason' => $item['discount_reason'] ?? null,
                    'discounted_by_user_id' => $item['discount_amount'] > 0 ? $user->id : null,
                    'discount_approved_by_user_id' => $sale->discount_approved_by_user_id,
                    'landed_cost_at_sale' => $allocation['batch']->landed_cost_price,
                    'is_accessory_free' => false,
                    'free_source' => $item['free_source'],
                ]);

                $costOfGoods += $allocation['batch']->landed_cost_price * $quantity * $item['conversion_factor'];
            }

            if ($allocatedUnits !== $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => 'The requested quantity could not be allocated from valid, unexpired batches.',
                ]);
            }
        }

        return round($costOfGoods, 4);
    }

    private function allocateStock(array $item, int $branchId): array
    {
        $batches = Batch::query()
            ->where('branch_id', $branchId)
            ->where('medicine_id', $item['medicine_id'])
            ->where('is_active', true)
            ->where('remaining_quantity_base', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->when(
                $item['dispensing_method'] === 'FIFO',
                fn ($query) => $query->orderBy('received_date')->orderBy('created_at'),
                fn ($query) => $query->orderBy('expiry_date')->orderBy('received_date'),
            )
            ->lockForUpdate()
            ->get();

        $remainingUnits = $item['quantity'];
        $allocations = [];
        $conversionFactor = $item['conversion_factor'];

        foreach ($batches as $batch) {
            $availableUnits = intdiv((int) $batch->remaining_quantity_base, $conversionFactor);
            $units = min($remainingUnits, $availableUnits);

            if ($units < 1) {
                continue;
            }

            $baseQuantity = $units * $conversionFactor;
            $batch->decrement('remaining_quantity_base', $baseQuantity);
            $batch->refresh();
            $allocations[] = ['batch' => $batch, 'quantity' => $units];
            $remainingUnits -= $units;

            if ($remainingUnits === 0) {
                break;
            }
        }

        if ($remainingUnits > 0) {
            throw ValidationException::withMessages([
                'items' => 'Insufficient unexpired stock for one or more requested medicines.',
            ]);
        }

        return $allocations;
    }

    private function payments(Sale $sale, PosSession $session, ?Contact $customer, float $patientDue, array $rows): array
    {
        $cash = 0.0;
        $card = 0.0;
        $credit = 0.0;
        $cashRows = [];
        $methods = [];
        $networkTreasuries = [];
        $drawer = Treasury::query()
            ->whereKey($session->treasury_id)
            ->where('branch_id', $session->branch_id)
            ->where('type', 'cash_drawer')
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();

        foreach ($rows as $index => $row) {
            $amount = round((float) ($row['amount'] ?? 0), 4);
            $method = $row['payment_method'] ?? '';
            if ($amount <= 0 || ! in_array($method, ['cash', 'card', 'credit'], true)) {
                throw ValidationException::withMessages([
                    "payments.$index" => 'Each payment must have a supported method and positive amount.',
                ]);
            }

            if ($method === 'cash') {
                $cash += $amount;
                $cashRows[] = $index;
            } elseif ($method === 'card') {
                $treasuryId = $row['treasury_id'] ?? null;
                if ($treasuryId === null) {
                    throw ValidationException::withMessages([
                        "payments.$index.treasury_id" => 'Choose the bank or network treasury for this card payment.',
                    ]);
                }
                $networkTreasury = Treasury::query()
                    ->whereKey($treasuryId)
                    ->where('branch_id', $session->branch_id)
                    ->where('type', 'bank')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();
                if ($networkTreasury === null) {
                    throw ValidationException::withMessages([
                        "payments.$index.treasury_id" => 'Select an active bank or network treasury in the session branch.',
                    ]);
                }
                $networkTreasuries[$index] = $networkTreasury;
                $card += $amount;
            } else {
                $credit += $amount;
            }

            $methods[] = $method;
        }

        $tendered = round($cash + $card, 4);
        $change = 0.0;

        if ($tendered > $patientDue) {
            if ($card > $patientDue || $credit > 0) {
                throw ValidationException::withMessages([
                    'payments' => 'Card or credit payments cannot exceed the amount due.',
                ]);
            }
            $change = round($tendered - $patientDue, 4);
            $credit = 0;
            $methods = array_values(array_filter($methods, fn ($method) => $method !== 'credit'));
        } elseif (round($tendered + $credit, 4) !== round($patientDue, 4)) {
            throw ValidationException::withMessages([
                'payments' => 'Payments and customer credit must exactly match the patient amount due.',
            ]);
        }

        if ($credit > 0) {
            if ($customer === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'A customer is required for credit sales.',
                ]);
            }

            $outstanding = (float) Sale::query()
                ->where('customer_id', $customer->id)
                ->selectRaw('COALESCE(SUM(patient_due - paid_amount), 0) as balance')
                ->value('balance');
            if ($customer->credit_limit <= 0 || $outstanding + $credit > (float) $customer->credit_limit) {
                throw ValidationException::withMessages([
                    'payments' => 'The sale exceeds the customer credit limit.',
                ]);
            }
        }

        $cashApplied = max(0, round($cash - $change, 4));
        if ($cashApplied > 0 && $cashRows !== []) {
            $remainingChange = $change;
            foreach (array_reverse($cashRows) as $index) {
                $amount = (float) $rows[$index]['amount'];
                $deduction = min($amount, $remainingChange);
                $rows[$index]['amount'] = round($amount - $deduction, 4);
                $remainingChange = round($remainingChange - $deduction, 4);
            }
        }

        $settlements = [];
        foreach ($rows as $index => $row) {
            if ((float) ($row['amount'] ?? 0) <= 0) {
                continue;
            }

            $treasury = match ($row['payment_method']) {
                'cash' => $drawer,
                'card' => $networkTreasuries[$index],
                default => null,
            };
            SalePayment::query()->create([
                'sale_id' => $sale->id,
                'treasury_id' => $treasury?->id,
                'payment_method' => $row['payment_method'],
                'amount' => $row['amount'],
                'reference' => $row['reference'] ?? null,
            ]);
            if ($treasury !== null) {
                $settlements[$treasury->id] = [
                    'treasury_id' => $treasury->id,
                    'account_id' => $treasury->account_id,
                    'amount' => round(($settlements[$treasury->id]['amount'] ?? 0) + (float) $row['amount'], 4),
                ];
            }
        }

        $paidAmount = min($patientDue, $tendered);
        $methodSet = array_values(array_unique(array_filter($methods)));
        $paymentMethod = count($methodSet) > 1
            ? 'mixed'
            : ($methodSet[0] ?? 'credit');

        return [
            'paid_amount' => round($paidAmount, 4),
            'change_amount' => $change,
            'cash_received' => round($cashApplied, 4),
            'payment_method' => $paymentMethod,
            'credit_amount' => round($credit, 4),
            'tendered_amount' => round($tendered, 4),
            'settlements' => array_values($settlements),
        ];
    }

    private function postJournal(
        Sale $sale,
        PosSession $session,
        ?Contact $customer,
        ?int $insurerContactId,
        array $payment,
        float $costOfGoods,
        Setting $setting,
        User $user,
    ): void {
        $lines = [];
        foreach ($payment['settlements'] as $settlement) {
            $this->addLine($lines, $settlement['account_id'], $settlement['amount'], 0, 'Sale proceeds');
        }
        if ($payment['credit_amount'] > 0) {
            $this->addLine($lines, $this->receivableAccount($customer), $payment['credit_amount'], 0, 'Customer receivable');
        }
        if ((float) $sale->insurance_due > 0) {
            $insurer = Contact::query()->findOrFail($insurerContactId);
            $this->addLine($lines, $this->receivableAccount($insurer), (float) $sale->insurance_due, 0, 'Insurance receivable');
        }
        if ($costOfGoods > 0) {
            if ($setting->cogs_account_id === null || $setting->inventory_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure inventory and cost-of-goods accounts before selling stock.',
                ]);
            }
            $this->addLine($lines, $setting->cogs_account_id, $costOfGoods, 0, 'Cost of goods sold');
            $this->addLine($lines, $setting->inventory_account_id, 0, $costOfGoods, 'Inventory relieved');
        }

        $netSales = round((float) $sale->grand_total - (float) $sale->tax_amount, 4);
        if ($netSales > 0) {
            if ($setting->sales_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the sales account before selling.',
                ]);
            }
            $this->addLine($lines, $setting->sales_account_id, 0, $netSales, 'Net sales');
        }
        if ((float) $sale->tax_amount > 0) {
            if ($setting->vat_payable_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the VAT payable account before taxable sales.',
                ]);
            }
            $this->addLine($lines, $setting->vat_payable_account_id, 0, (float) $sale->tax_amount, 'Output VAT');
        }

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 4);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 4);

        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'accounting' => 'The sale could not be posted because its journal entry is not balanced.',
            ]);
        }

        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $sale->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => today(),
            'description' => 'Automatic posting for sale '.$sale->invoice_number,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
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
        $amount = round(max($debit, $credit), 4);
        if ($amount > 0) {
            $lines[] = [
                'account_id' => $accountId,
                'debit' => round($debit, 4),
                'credit' => round($credit, 4),
                'memo' => $memo,
            ];
        }
    }

    private function receivableAccount(?Contact $contact): int
    {
        if ($contact?->customer_account_id !== null) {
            return (int) $contact->customer_account_id;
        }

        return (int) Account::query()->where('code', '1301')->firstOrFail()->id;
    }

    private function invoiceNumber(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    private function claimNumber(): string
    {
        return 'CLM-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
