<?php

namespace App\Services\Pharmacy;

use Illuminate\Validation\ValidationException;

class SalePricingService
{
    public function price(
        array $items,
        float $invoiceDiscount,
        float $taxRate,
        float $discountApprovalLimit,
        bool $canApproveDiscount,
        ?float $planCoverageLimit = null,
    ): array {
        $subtotal = 0.0;
        $itemDiscounts = 0.0;
        $discountableSubtotal = 0.0;

        foreach ($items as $index => &$item) {
            $gross = $this->money((float) $item['unit_price'] * (int) $item['quantity']);
            $freeSource = $item['free_source'] ?? 'none';
            $discount = $freeSource === 'pharmacy'
                ? $gross
                : $this->money((float) ($item['discount_amount'] ?? 0));

            if ($discount > $gross) {
                throw ValidationException::withMessages([
                    "items.$index.discount_amount" => 'The item discount cannot exceed its gross amount.',
                ]);
            }

            if ($freeSource === 'pharmacy' && (float) ($item['discount_amount'] ?? 0) > 0) {
                throw ValidationException::withMessages([
                    "items.$index.discount_amount" => 'Use either a pharmacy-free item or a monetary discount.',
                ]);
            }

            if ($discount > 0 && blank($item['discount_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    "items.$index.discount_reason" => 'A reason is required for every item discount.',
                ]);
            }

            $item['gross_amount'] = $gross;
            $item['item_discount'] = $discount;
            $item['net_before_invoice_discount'] = $this->money($gross - $discount);
            $item['tax_rate'] = (bool) ($item['is_taxable'] ?? true) ? $taxRate : 0.0;
            $subtotal += $gross;
            $itemDiscounts += $discount;
            $discountableSubtotal += $item['net_before_invoice_discount'];
        }
        unset($item);

        $subtotal = $this->money($subtotal);
        $itemDiscounts = $this->money($itemDiscounts);
        $invoiceDiscount = $this->money($invoiceDiscount);

        if ($invoiceDiscount > $discountableSubtotal) {
            throw ValidationException::withMessages([
                'invoice_discount' => 'The invoice discount cannot exceed the remaining subtotal.',
            ]);
        }

        $totalDiscount = $this->money($itemDiscounts + $invoiceDiscount);
        $discountPercentage = $subtotal > 0 ? ($totalDiscount / $subtotal) * 100 : 0;

        if ($discountPercentage > $discountApprovalLimit && ! $canApproveDiscount) {
            throw ValidationException::withMessages([
                'discount' => "Discounts above {$discountApprovalLimit}% require administrator approval.",
            ]);
        }

        $remainingInvoiceDiscount = $invoiceDiscount;
        $lastDiscountableIndex = null;
        foreach ($items as $index => $item) {
            if ($item['net_before_invoice_discount'] > 0) {
                $lastDiscountableIndex = $index;
            }
        }

        foreach ($items as $index => &$item) {
            $weight = $discountableSubtotal > 0
                ? $item['net_before_invoice_discount'] / $discountableSubtotal
                : 0;
            $allocatedInvoiceDiscount = $index === $lastDiscountableIndex
                ? $remainingInvoiceDiscount
                : $this->money($invoiceDiscount * $weight);
            $allocatedInvoiceDiscount = min($allocatedInvoiceDiscount, $item['net_before_invoice_discount']);
            $remainingInvoiceDiscount = $this->money($remainingInvoiceDiscount - $allocatedInvoiceDiscount);
            $item['discount_amount'] = $this->money($item['item_discount'] + $allocatedInvoiceDiscount);
            $item['net_amount'] = $this->money($item['gross_amount'] - $item['discount_amount']);
            $coveragePercentage = (float) ($item['coverage_percentage'] ?? 0);
            $copayPercentage = (float) ($item['patient_copay_percentage'] ?? 0);

            if (($item['free_source'] ?? 'none') === 'insurance') {
                if (! ($item['is_insurance_covered'] ?? false) || $copayPercentage > 0 || $coveragePercentage < 100) {
                    throw ValidationException::withMessages([
                        "items.$index.free_source" => 'Insurance-free items require 100% coverage and no patient copay.',
                    ]);
                }

                $coveragePercentage = 100;
            }

            $effectiveCoveragePercentage = min($coveragePercentage, 100 - $copayPercentage);
            $coveredBase = $this->money($item['net_amount'] * $effectiveCoveragePercentage / 100);
            $itemLimit = $item['max_coverage_amount'] ?? null;
            if ($itemLimit !== null) {
                $coveredBase = min($coveredBase, (float) $itemLimit);
            }

            $item['covered_base_amount'] = $coveredBase;
            $item['tax_amount'] = $this->money($item['net_amount'] * $item['tax_rate'] / 100);
        }
        unset($item);

        $coverageTotal = $this->money(array_sum(array_column($items, 'covered_base_amount')));
        if ($planCoverageLimit !== null && $coverageTotal > $planCoverageLimit && $coverageTotal > 0) {
            $remainingLimit = $this->money($planCoverageLimit);
            $lastCoveredIndex = null;
            foreach ($items as $index => $item) {
                if ($item['covered_base_amount'] > 0) {
                    $lastCoveredIndex = $index;
                }
            }

            foreach ($items as $index => &$item) {
                if ($item['covered_base_amount'] <= 0) {
                    continue;
                }

                $allocated = $index === $lastCoveredIndex
                    ? $remainingLimit
                    : $this->money($planCoverageLimit * $item['covered_base_amount'] / $coverageTotal);
                $item['covered_base_amount'] = min($allocated, $item['covered_base_amount']);
                $remainingLimit = $this->money($remainingLimit - $item['covered_base_amount']);
            }
            unset($item);
        }

        $taxTotal = 0.0;
        $grandTotal = 0.0;
        $insuranceDue = 0.0;

        foreach ($items as &$item) {
            $insuranceTax = $item['net_amount'] > 0
                ? $this->money($item['tax_amount'] * $item['covered_base_amount'] / $item['net_amount'])
                : 0.0;
            $item['insurance_covered_amount'] = $this->money($item['covered_base_amount'] + $insuranceTax);
            $item['line_total'] = $this->money($item['net_amount'] + $item['tax_amount']);
            $item['patient_amount'] = $this->money($item['line_total'] - $item['insurance_covered_amount']);

            if (($item['free_source'] ?? 'none') === 'insurance' && $item['patient_amount'] > 0) {
                throw ValidationException::withMessages([
                    'items' => 'The insurance plan limits prevent this item from being fully free to the patient.',
                ]);
            }

            $taxTotal += $item['tax_amount'];
            $grandTotal += $item['line_total'];
            $insuranceDue += $item['insurance_covered_amount'];
        }
        unset($item);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscount,
            'tax_amount' => $this->money($taxTotal),
            'grand_total' => $this->money($grandTotal),
            'insurance_due' => $this->money($insuranceDue),
            'patient_due' => $this->money($grandTotal - $insuranceDue),
            'discount_percentage' => $discountPercentage,
            'requires_approval' => $discountPercentage > $discountApprovalLimit,
        ];
    }

    private function money(float $amount): float
    {
        return round($amount, 4);
    }
}
