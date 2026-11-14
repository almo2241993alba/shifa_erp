<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Contact;
use App\Models\DosageForm;
use App\Models\JournalEntry;
use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.initial_admin_password' => 'secure-test-password-2026']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_purchase_receipt_and_supplier_return_update_stock_and_balanced_entries(): void
    {
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $branch = $user->branch;
        $category = Category::query()->firstOrFail();
        $dosageForm = DosageForm::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();
        $medicine = Medicine::query()->create([
            'category_id' => $category->id,
            'dosage_form_id' => $dosageForm->id,
            'trade_name' => 'Purchase test medicine',
            'item_type' => 'medicine',
            'dispensing_method' => 'FEFO',
            'is_taxable' => true,
            'is_active' => true,
        ]);
        MedicineUnit::query()->create([
            'medicine_id' => $medicine->id,
            'unit_id' => $unit->id,
            'conversion_factor' => 1,
            'selling_price' => 20,
            'is_base_unit' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
        ]);
        $supplier = Contact::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Test supplier',
            'is_supplier' => true,
            'is_active' => true,
        ]);

        $purchaseResponse = $this->actingAs($user)->postJson('/pharmacy/purchases', [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'received_date' => today()->toDateString(),
            'items' => [[
                'medicine_id' => $medicine->id,
                'unit_id' => $unit->id,
                'quantity' => 2,
                'bonus_quantity' => 1,
                'unit_purchase_price' => 10,
                'batch_number' => 'PB-001',
                'expiry_date' => today()->addYear()->toDateString(),
            ]],
        ]);
        $purchaseResponse->assertCreated();

        $purchase = Purchase::query()->firstOrFail();
        $batch = Batch::query()->firstOrFail();
        $this->assertSame(3, $batch->remaining_quantity_base);
        $this->assertSame(23.0, (float) $purchase->remaining_amount);

        $returnResponse = $this->actingAs($user)->postJson('/pharmacy/purchase-returns', [
            'purchase_id' => $purchase->id,
            'return_date' => today()->toDateString(),
            'reason' => 'Damaged packaging',
            'items' => [[
                'purchase_item_id' => $purchase->items()->firstOrFail()->id,
                'quantity' => 1,
            ]],
        ]);
        $returnResponse->assertCreated();

        $this->assertSame(2, $batch->refresh()->remaining_quantity_base);
        $this->assertSame(11.5, (float) PurchaseReturn::query()->firstOrFail()->total_return_amount + (float) PurchaseReturn::query()->firstOrFail()->tax_return_amount);
        $this->assertSame(11.5, (float) $purchase->refresh()->remaining_amount);
        $this->assertSame(2, JournalEntry::query()->count());
        foreach (JournalEntry::query()->get() as $entry) {
            $this->assertSame((float) $entry->total_debit, (float) $entry->total_credit);
        }
    }
}
