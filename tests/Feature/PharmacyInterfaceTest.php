<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DosageForm;
use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_management_screen_uses_the_pharmacy_workspace(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->withSession(['locale' => 'ar'])
            ->get('/pharmacy/manage/categories')
            ->assertOk()
            ->assertSee('تصنيفات الأدوية')
            ->assertSee('data-management-root', false);
    }

    public function test_cashier_screen_renders_and_product_lookup_is_branch_safe(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->withSession(['locale' => 'ar'])
            ->get('/pharmacy/cashier')
            ->assertOk()
            ->assertSee('سلة البيع')
            ->assertSee('data-cashier-root', false)
            ->assertSee('فتح جلسة جديدة');

        $this->getJson('/pharmacy/cashier/products')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_cashier_product_search_loads_only_current_branch_batches(): void
    {
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
        $otherBranch = Branch::create(['name' => 'Other', 'code' => 'OTHER']);
        $category = Category::create(['name' => 'Test category']);
        $dosageForm = DosageForm::create(['name' => 'Tablet']);
        $unit = Unit::create(['name' => 'Tablet unit']);
        $medicine = Medicine::create([
            'category_id' => $category->id,
            'dosage_form_id' => $dosageForm->id,
            'trade_name' => 'Test medicine',
        ]);
        MedicineUnit::create([
            'medicine_id' => $medicine->id,
            'unit_id' => $unit->id,
            'selling_price' => 10,
            'is_base_unit' => true,
            'is_sellable' => true,
        ]);

        foreach ([$branch, $otherBranch] as $stockBranch) {
            Batch::create([
                'branch_id' => $stockBranch->id,
                'medicine_id' => $medicine->id,
                'batch_number' => 'BATCH-'.$stockBranch->code,
                'expiry_date' => today()->addMonth()->toDateString(),
                'received_date' => today()->toDateString(),
                'initial_quantity_base' => 10,
                'remaining_quantity_base' => 10,
                'unit_purchase_price' => 5,
                'landed_cost_price' => 5,
            ]);
        }

        $this->actingAs(User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]))
            ->getJson('/pharmacy/cashier/products')
            ->assertOk()
            ->assertJsonPath('data.0.trade_name', 'Test medicine')
            ->assertJsonCount(1, 'data.0.batches')
            ->assertJsonPath('data.0.batches.0.branch_id', $branch->id);
    }
}
