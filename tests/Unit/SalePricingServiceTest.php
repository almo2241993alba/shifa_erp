<?php

namespace Tests\Unit;

use App\Services\Pharmacy\SalePricingService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalePricingServiceTest extends TestCase
{
    public function test_discount_is_applied_before_insurance_and_tax_is_split_by_coverage(): void
    {
        $result = (new SalePricingService())->price([
            $this->item([
                'discount_amount' => 10,
                'discount_reason' => 'Promotion',
                'coverage_percentage' => 80,
                'patient_copay_percentage' => 20,
            ]),
        ], 0, 15, 5, true);

        $this->assertSame(90.0, $result['items'][0]['net_amount']);
        $this->assertSame(13.5, $result['tax_amount']);
        $this->assertSame(82.8, $result['insurance_due']);
        $this->assertSame(20.7, $result['patient_due']);
        $this->assertSame(103.5, $result['grand_total']);
    }

    public function test_invoice_discount_is_allocated_and_requires_approval_above_the_configured_limit(): void
    {
        $result = (new SalePricingService())->price([
            $this->item(['coverage_percentage' => 0]),
            $this->item(['unit_price' => 50, 'coverage_percentage' => 0]),
        ], 10, 0, 5, true);

        $this->assertSame(10.0, $result['discount_amount']);
        $this->assertSame(140.0, $result['grand_total']);
        $this->assertTrue($result['requires_approval']);
    }

    public function test_discount_above_the_configured_limit_is_rejected_without_admin_approval(): void
    {
        $this->expectException(ValidationException::class);

        (new SalePricingService())->price([
            $this->item([
                'discount_amount' => 10,
                'discount_reason' => 'Promotion',
                'coverage_percentage' => 0,
            ]),
        ], 0, 0, 5, false);
    }

    public function test_pharmacy_free_items_are_not_claimed_and_insurance_free_items_require_full_cover(): void
    {
        $service = new SalePricingService();
        $pharmacyFree = $service->price([
            $this->item([
                'free_source' => 'pharmacy',
                'discount_reason' => 'Promotion',
                'coverage_percentage' => 100,
            ]),
        ], 0, 15, 100, true);

        $this->assertSame(0.0, $pharmacyFree['patient_due']);
        $this->assertSame(0.0, $pharmacyFree['insurance_due']);
        $this->assertSame(0.0, $pharmacyFree['tax_amount']);

        $insuranceFree = $service->price([
            $this->item([
                'free_source' => 'insurance',
                'coverage_percentage' => 100,
                'is_insurance_covered' => true,
            ]),
        ], 0, 15, 100, true);

        $this->assertSame(0.0, $insuranceFree['patient_due']);
        $this->assertSame(115.0, $insuranceFree['insurance_due']);
    }

    private function item(array $overrides = []): array
    {
        return array_replace([
            'unit_price' => 100,
            'quantity' => 1,
            'discount_amount' => 0,
            'discount_reason' => null,
            'free_source' => 'none',
            'is_taxable' => true,
            'coverage_percentage' => 0,
            'patient_copay_percentage' => 0,
            'is_insurance_covered' => false,
        ], $overrides);
    }
}
