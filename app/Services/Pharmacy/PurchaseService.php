<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\Batch;
use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\Purchase;
use App\Models\PurchaseAdditionalCost;
use App\Models\PurchaseItem;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function create(User $user, array $data): Purchase
    {
        return DB::transaction(function () use ($user, $data): Purchase {
            $branchId = (int) ($data['branch_id'] ?? $user->branch_id);
            if ($branchId < 1) {
                throw ValidationException::withMessages([
                    'branch_id' => 'A branch is required to receive stock.',
                ]);
            }
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before receiving stock.');
            }
            if ($user->branch_id !== null && $branchId !== (int) $user->branch_id) {
                abort(403, 'You cannot receive stock for another branch.');
            }

            $supplier = Contact::query()->findOrFail($data['supplier_id']);
            if (! $supplier->is_active
                || ! $supplier->is_supplier
                || (int) $supplier->branch_id !== $branchId) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'The selected contact is not an active supplier in the purchase branch.',
                ]);
            }

            $setting = Setting::query()->firstOrFail();
            $preparedItems = $this->prepareItems($data['items'], (float) $setting->tax_rate);
            $subtotal = round(array_sum(array_column($preparedItems, 'gross_amount')), 4);
            $discount = round((float) ($data['discount_amount'] ?? 0), 4);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The purchase discount cannot exceed the subtotal.',
                ]);
            }

            $this->allocateDiscount($preparedItems, $discount, $subtotal, (float) $setting->tax_rate);
            $taxTotal = round(array_sum(array_column($preparedItems, 'tax_amount')), 4);
            $netGoods = round(array_sum(array_column($preparedItems, 'net_amount')), 4);
            $additionalCosts = $this->validateAdditionalCosts($data['additional_costs'] ?? [], $branchId);
            $additionalCostTotal = round(array_sum(array_column($additionalCosts, 'amount')), 4);
            $supplierTotal = round($netGoods + $taxTotal, 4);
            $grandTotal = round($supplierTotal + $additionalCostTotal, 4);
            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 4);

            if ($paidAmount > $supplierTotal) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'The supplier payment cannot exceed the supplier invoice amount.',
                ]);
            }

            $treasuryId = $data['treasury_id'] ?? null;
            if ($paidAmount > 0) {
                $this->assertTreasury($treasuryId, $branchId);
            } elseif ($treasuryId !== null) {
                $this->assertTreasury((int) $treasuryId, $branchId);
            }

            $requiredOutflows = [];
            if ($paidAmount > 0) {
                $requiredOutflows[$treasuryId] = $paidAmount;
            }
            foreach ($additionalCosts as $cost) {
                $requiredOutflows[$cost['treasury_id']] = ($requiredOutflows[$cost['treasury_id']] ?? 0)
                    + $cost['amount'];
            }
            foreach ($requiredOutflows as $outflowTreasuryId => $outflowAmount) {
                $outflowTreasury = Treasury::query()->lockForUpdate()->findOrFail($outflowTreasuryId);
                if ((float) $outflowTreasury->current_balance < $outflowAmount) {
                    throw ValidationException::withMessages([
                        'treasury_id' => 'The selected treasury does not have enough balance for all purchase payments and costs.',
                    ]);
                }
            }

            $financialTransaction = FinancialTransaction::query()->create();
            $purchase = Purchase::query()->create([
                'financial_transaction_id' => $financialTransaction->id,
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'treasury_id' => $treasuryId,
                'purchase_number' => $this->purchaseNumber(),
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'received_date' => $data['received_date'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'additional_costs_total' => $additionalCostTotal,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => round($supplierTotal - $paidAmount, 4),
                'payment_status' => $paidAmount >= $supplierTotal
                    ? 'paid'
                    : ($paidAmount > 0 ? 'partially_paid' : 'unpaid'),
                'status' => 'received',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->receiveItems($purchase, $preparedItems, $branchId);
            foreach ($additionalCosts as $cost) {
                PurchaseAdditionalCost::query()->create([
                    'purchase_id' => $purchase->id,
                    ...$cost,
                ]);
                Treasury::query()->whereKey($cost['treasury_id'])->decrement('current_balance', $cost['amount']);
            }

            if ($paidAmount > 0) {
                Treasury::query()->whereKey($treasuryId)->decrement('current_balance', $paidAmount);
            }

            $this->postJournal(
                $purchase,
                $supplier,
                $preparedItems,
                $additionalCostTotal,
                $paidAmount,
                $treasuryId,
                $setting,
                $user,
            );

            return $purchase->load([
                'supplier',
                'items.medicine',
                'items.unit',
                'items.batch',
                'additionalCosts.treasury',
            ]);
        });
    }

    private function prepareItems(array $requestedItems, float $taxRate): array
    {
        $items = [];

        foreach ($requestedItems as $index => $requested) {
            $medicine = Medicine::query()->findOrFail($requested['medicine_id']);
            if (! $medicine->is_active) {
                throw ValidationException::withMessages([
                    "items.$index.medicine_id" => 'Inactive medicines cannot be received.',
                ]);
            }

            $unit = MedicineUnit::query()
                ->where('medicine_id', $medicine->id)
                ->where('unit_id', $requested['unit_id'])
                ->where('is_purchasable', true)
                ->first();
            if ($unit === null || $unit->conversion_factor < 1) {
                throw ValidationException::withMessages([
                    "items.$index.unit_id" => 'The selected unit is not configured for purchasing.',
                ]);
            }

            $quantity = (int) $requested['quantity'];
            $bonus = (int) ($requested['bonus_quantity'] ?? 0);
            $unitPrice = round((float) $requested['unit_purchase_price'], 4);
            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.unit_purchase_price" => 'The purchase price must be greater than zero.',
                ]);
            }
            if (! empty($requested['production_date'])
                && $requested['production_date'] > $requested['expiry_date']) {
                throw ValidationException::withMessages([
                    "items.$index.production_date" => 'The production date cannot be after the expiry date.',
                ]);
            }
            $gross = round($quantity * $unitPrice, 4);
            $items[] = [
                'medicine' => $medicine,
                'medicine_unit' => $unit,
                'unit_id' => $unit->unit_id,
                'quantity' => $quantity,
                'bonus_quantity' => $bonus,
                'total_base_quantity' => ($quantity + $bonus) * $unit->conversion_factor,
                'unit_purchase_price' => $unitPrice,
                'new_selling_price' => $requested['new_selling_price'] ?? null,
                'batch_number' => $requested['batch_number'],
                'production_date' => $requested['production_date'] ?? null,
                'expiry_date' => $requested['expiry_date'],
                'gross_amount' => $gross,
                'tax_amount' => $medicine->is_taxable
                    ? round($gross * $taxRate / 100, 4)
                    : 0,
            ];
        }

        return $items;
    }

    private function allocateDiscount(array &$items, float $discount, float $subtotal, float $taxRate): void
    {
        $remaining = $discount;
        $lastIndex = count($items) - 1;
        foreach ($items as $index => &$item) {
            $allocated = $index === $lastIndex || $subtotal === 0.0
                ? $remaining
                : round($discount * $item['gross_amount'] / $subtotal, 4);
            $allocated = min($allocated, $item['gross_amount']);
            $remaining = round($remaining - $allocated, 4);
            $item['discount_amount'] = $allocated;
            $item['net_amount'] = round($item['gross_amount'] - $allocated, 4);
            if ($item['medicine']->is_taxable) {
                $item['tax_amount'] = round($item['net_amount'] * $taxRate / 100, 4);
            }
        }
        unset($item);
    }

    private function validateAdditionalCosts(array $costs, int $branchId): array
    {
        $result = [];
        foreach ($costs as $index => $cost) {
            $treasury = $this->assertTreasury($cost['treasury_id'] ?? null, $branchId);
            $amount = round((float) $cost['amount'], 4);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    "additional_costs.$index.amount" => 'Additional costs must be greater than zero.',
                ]);
            }
            $result[] = [
                'treasury_id' => $treasury->id,
                'cost_type' => $cost['cost_type'],
                'amount' => $amount,
                'notes' => $cost['notes'] ?? null,
            ];
        }

        return $result;
    }

    private function receiveItems(Purchase $purchase, array $items, int $branchId): void
    {
        $additionalTotal = (float) $purchase->additional_costs_total;
        $netTotal = max(0.0001, (float) $purchase->subtotal - (float) $purchase->discount_amount);
        $remainingAdditional = $additionalTotal;

        foreach ($items as $index => $item) {
            $allocatedAdditional = $index === array_key_last($items)
                ? $remainingAdditional
                : round($additionalTotal * $item['net_amount'] / $netTotal, 4);
            $remainingAdditional = round($remainingAdditional - $allocatedAdditional, 4);
            $landedCost = round(($item['net_amount'] + $allocatedAdditional) / $item['total_base_quantity'], 4);
            $unitPurchasePrice = round($item['unit_purchase_price'] / $item['medicine_unit']->conversion_factor, 4);

            $batch = Batch::query()->create([
                'branch_id' => $branchId,
                'medicine_id' => $item['medicine']->id,
                'batch_number' => $item['batch_number'],
                'production_date' => $item['production_date'],
                'expiry_date' => $item['expiry_date'],
                'received_date' => $purchase->received_date,
                'initial_quantity_base' => $item['total_base_quantity'],
                'remaining_quantity_base' => $item['total_base_quantity'],
                'unit_purchase_price' => $unitPurchasePrice,
                'landed_cost_price' => $landedCost,
                'is_active' => true,
            ]);

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'medicine_id' => $item['medicine']->id,
                'unit_id' => $item['unit_id'],
                'batch_id' => $batch->id,
                'batch_number' => $item['batch_number'],
                'production_date' => $item['production_date'],
                'expiry_date' => $item['expiry_date'],
                'quantity' => $item['quantity'],
                'bonus_quantity' => $item['bonus_quantity'],
                'total_base_quantity' => $item['total_base_quantity'],
                'unit_purchase_price' => $item['unit_purchase_price'],
                'new_selling_price' => $item['new_selling_price'],
                'tax_amount' => $item['tax_amount'],
                'allocated_additional_cost' => $allocatedAdditional,
                'landed_cost_per_base_unit' => $landedCost,
                'line_total' => round($item['net_amount'] + $item['tax_amount'], 4),
            ]);

            if ($item['new_selling_price'] !== null) {
                $item['medicine_unit']->forceFill([
                    'selling_price' => $item['new_selling_price'],
                ])->save();
            }
        }
    }

    private function postJournal(
        Purchase $purchase,
        Contact $supplier,
        array $items,
        float $additionalCosts,
        float $paidAmount,
        ?int $treasuryId,
        Setting $setting,
        User $user,
    ): void {
        if ($setting->inventory_account_id === null) {
            throw ValidationException::withMessages([
                'settings' => 'Configure the inventory account before receiving purchases.',
            ]);
        }

        $netGoods = round((float) $purchase->subtotal - (float) $purchase->discount_amount, 4);
        $tax = (float) $purchase->tax_amount;
        $supplierTotal = round($netGoods + $tax, 4);
        $lines = [];
        $this->appendLine($lines, $setting->inventory_account_id, $netGoods + $additionalCosts, 0, 'Inventory received');

        if ($tax > 0) {
            if ($setting->vat_receivable_account_id === null) {
                throw ValidationException::withMessages([
                    'settings' => 'Configure the VAT receivable account before taxable purchases.',
                ]);
            }
            $this->appendLine($lines, $setting->vat_receivable_account_id, $tax, 0, 'Input VAT');
        }
        if ($paidAmount > 0) {
            $treasury = Treasury::query()->findOrFail($treasuryId);
            $this->appendLine($lines, $treasury->account_id, 0, $paidAmount, 'Supplier payment');
        }
        foreach ($purchase->additionalCosts as $cost) {
            $this->appendLine($lines, $cost->treasury->account_id, 0, (float) $cost->amount, $cost->cost_type);
        }
        $remainingSupplierAmount = round($supplierTotal - $paidAmount, 4);
        if ($remainingSupplierAmount > 0) {
            $payableAccount = $supplier->supplier_account_id
                ?? Account::query()->where('code', '2101')->firstOrFail()->id;
            $this->appendLine($lines, (int) $payableAccount, 0, $remainingSupplierAmount, 'Supplier payable');
        }

        $debit = round(array_sum(array_column($lines, 'debit')), 4);
        $credit = round(array_sum(array_column($lines, 'credit')), 4);
        if ($debit !== $credit) {
            throw ValidationException::withMessages([
                'accounting' => 'The purchase could not be posted because its journal entry is not balanced.',
            ]);
        }

        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $purchase->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => $purchase->purchase_date,
            'description' => 'Automatic posting for purchase '.$purchase->purchase_number,
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

    private function assertTreasury(?int $treasuryId, int $branchId): Treasury
    {
        if ($treasuryId === null) {
            throw ValidationException::withMessages([
                'treasury_id' => 'A treasury is required for every purchase payment and additional cost.',
            ]);
        }

        return Treasury::query()
            ->whereKey($treasuryId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function appendLine(array &$lines, int $accountId, float $debit, float $credit, string $memo): void
    {
        if (max($debit, $credit) <= 0) {
            return;
        }
        $lines[] = [
            'account_id' => $accountId,
            'debit' => round($debit, 4),
            'credit' => round($credit, 4),
            'memo' => $memo,
        ];
    }

    private function purchaseNumber(): string
    {
        return 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
