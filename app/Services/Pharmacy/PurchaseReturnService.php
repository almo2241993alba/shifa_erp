<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    public function create(User $user, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $data): PurchaseReturn {
            $purchase = Purchase::query()->lockForUpdate()
                ->with(['supplier', 'items'])
                ->findOrFail($data['purchase_id']);
            if ($purchase->status !== 'received') {
                throw ValidationException::withMessages([
                    'purchase_id' => 'Only received purchases can be returned.',
                ]);
            }
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before processing purchase returns.');
            }
            if ($user->branch_id !== null && (int) $purchase->branch_id !== (int) $user->branch_id) {
                abort(403, 'You cannot return stock for another branch.');
            }

            $items = $this->prepareItems($purchase, $data['items']);
            $total = round(array_sum(array_column($items, 'net_amount')), 4);
            $taxTotal = round(array_sum(array_column($items, 'tax_amount')), 4);
            $supplierRefund = round($total + $taxTotal, 4);
            $payableOffset = min($supplierRefund, (float) $purchase->remaining_amount);
            $cashRefund = round($supplierRefund - $payableOffset, 4);
            $treasury = null;
            if ($cashRefund > 0 && empty($data['treasury_id'])) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'A treasury is required when the return exceeds the unpaid supplier balance.',
                ]);
            }
            if (! empty($data['treasury_id'])) {
                $treasury = Treasury::query()
                    ->whereKey($data['treasury_id'])
                    ->where('branch_id', $purchase->branch_id)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            $return = PurchaseReturn::query()->create([
                'financial_transaction_id' => FinancialTransaction::query()->create()->id,
                'branch_id' => $purchase->branch_id,
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => $user->id,
                'treasury_id' => $treasury?->id,
                'return_number' => 'PRET-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'return_date' => $data['return_date'],
                'total_return_amount' => $total,
                'tax_return_amount' => $taxTotal,
                'reason' => $data['reason'],
            ]);

            $inventoryCost = 0.0;
            foreach ($items as $item) {
                $item['batch']->decrement('remaining_quantity_base', $item['base_quantity']);
                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $item['purchase_item']->id,
                    'medicine_id' => $item['purchase_item']->medicine_id,
                    'unit_id' => $item['purchase_item']->unit_id,
                    'batch_id' => $item['batch']->id,
                    'returned_quantity' => $item['quantity'],
                    'base_quantity_deducted' => $item['base_quantity'],
                    'unit_purchase_price' => $item['purchase_item']->unit_purchase_price,
                    'tax_return_amount' => $item['tax_amount'],
                    'line_total' => $item['net_amount'],
                ]);
                $inventoryCost += (float) $item['batch']->landed_cost_price * $item['base_quantity'];
            }

            if ($cashRefund > 0 && $treasury !== null) {
                $treasury->increment('current_balance', $cashRefund);
            }

            if ($payableOffset > 0) {
                $purchase->remaining_amount = round((float) $purchase->remaining_amount - $payableOffset, 4);
                $purchase->payment_status = (float) $purchase->remaining_amount === 0.0
                    ? 'paid'
                    : ((float) $purchase->paid_amount > 0 ? 'partially_paid' : 'unpaid');
                $purchase->save();
            }

            $this->postJournal($return, $purchase->supplier, $treasury, $payableOffset, $cashRefund, $inventoryCost, $user);

            return $return->load(['purchase', 'supplier', 'items.medicine', 'items.batch', 'treasury']);
        });
    }

    private function prepareItems(Purchase $purchase, array $requestedItems): array
    {
        $results = [];
        $seenPurchaseItems = [];
        foreach ($requestedItems as $index => $requested) {
            $purchaseItemId = (int) $requested['purchase_item_id'];
            if (in_array($purchaseItemId, $seenPurchaseItems, true)) {
                throw ValidationException::withMessages([
                    "items.$index.purchase_item_id" => 'Each purchase line can appear only once in a return.',
                ]);
            }
            $seenPurchaseItems[] = $purchaseItemId;
            $purchaseItem = PurchaseItem::query()->lockForUpdate()
                ->where('purchase_id', $purchase->id)
                ->findOrFail($purchaseItemId);
            $quantity = (int) $requested['quantity'];
            $alreadyReturned = (int) $purchaseItem->returnItems()->sum('returned_quantity');
            if ($quantity < 1 || $alreadyReturned + $quantity > (int) $purchaseItem->quantity) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'The requested quantity exceeds the unreturned purchased quantity.',
                ]);
            }

            $batch = $purchaseItem->batch()->lockForUpdate()->firstOrFail();
            $basePerUnit = intdiv(
                (int) $purchaseItem->total_base_quantity,
                (int) ($purchaseItem->quantity + $purchaseItem->bonus_quantity),
            );
            $baseQuantity = $quantity * $basePerUnit;
            if ((int) $batch->remaining_quantity_base < $baseQuantity) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'The batch does not have enough stock for this return.',
                ]);
            }

            $discountRate = (float) $purchase->subtotal > 0
                ? (float) $purchase->discount_amount / (float) $purchase->subtotal
                : 0;
            $gross = round($quantity * (float) $purchaseItem->unit_purchase_price, 4);
            $net = round($gross * (1 - $discountRate), 4);
            $tax = round((float) $purchaseItem->tax_amount * $quantity / (int) $purchaseItem->quantity, 4);
            $results[] = [
                'purchase_item' => $purchaseItem,
                'batch' => $batch,
                'quantity' => $quantity,
                'base_quantity' => $baseQuantity,
                'net_amount' => $net,
                'tax_amount' => $tax,
            ];
        }

        return $results;
    }

    private function postJournal(
        PurchaseReturn $return,
        Contact $supplier,
        ?Treasury $treasury,
        float $payableOffset,
        float $cashRefund,
        float $inventoryCost,
        User $user,
    ): void {
        $settings = Setting::query()->firstOrFail();
        if ($settings->inventory_account_id === null) {
            throw ValidationException::withMessages([
                'settings' => 'Configure the inventory account before processing purchase returns.',
            ]);
        }

        $lines = [];
        if ($payableOffset > 0) {
            $accountId = $supplier->supplier_account_id
                ?? Account::query()->where('code', '2101')->firstOrFail()->id;
            $this->addLine($lines, (int) $accountId, $payableOffset, 0, 'Supplier payable reduced');
        }
        if ($cashRefund > 0) {
            if ($treasury === null) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'A treasury is required to record the cash refund.',
                ]);
            }
            $this->addLine($lines, $treasury->account_id, $cashRefund, 0, 'Supplier refund received');
        }
        if ((float) $return->tax_return_amount > 0) {
            if ($settings->vat_receivable_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the VAT receivable account before processing purchase returns.',
                ]);
            }
            $this->addLine($lines, $settings->vat_receivable_account_id, 0, (float) $return->tax_return_amount, 'Input VAT reversed');
        }
        if ($inventoryCost > 0) {
            $this->addLine($lines, $settings->inventory_account_id, 0, $inventoryCost, 'Inventory returned to supplier');
        }
        $variance = round((float) $return->total_return_amount - $inventoryCost, 4);
        if ($variance < 0) {
            $varianceAccount = Account::query()->where('code', '5301')->firstOrFail();
            $this->addLine($lines, $varianceAccount->id, abs($variance), 0, 'Non-recoverable purchase costs');
        } elseif ($variance > 0) {
            $varianceAccount = Account::query()->where('code', '5301')->firstOrFail();
            $this->addLine($lines, $varianceAccount->id, 0, $variance, 'Purchase return cost variance');
        }

        $debit = round(array_sum(array_column($lines, 'debit')), 4);
        $credit = round(array_sum(array_column($lines, 'credit')), 4);
        if ($debit !== $credit) {
            throw ValidationException::withMessages([
                'accounting' => 'The purchase return could not be posted because its journal entry is not balanced.',
            ]);
        }

        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $return->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => $return->return_date,
            'description' => 'Automatic posting for purchase return '.$return->return_number,
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
