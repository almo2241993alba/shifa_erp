<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Contact;
use App\Models\DosageForm;
use App\Models\InsuranceClaim;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\JournalEntry;
use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\PosSession;
use App\Models\SalePayment;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.initial_admin_password' => 'secure-test-password-2026']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sale_deducts_stock_and_creates_an_insurance_claim_and_balanced_journal(): void
    {
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $branch = $user->branch;
        $treasury = Treasury::query()->where('branch_id', $branch->id)->where('type', 'cash_drawer')->firstOrFail();
        $category = Category::query()->firstOrFail();
        $dosageForm = DosageForm::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();

        $medicine = Medicine::query()->create([
            'category_id' => $category->id,
            'dosage_form_id' => $dosageForm->id,
            'trade_name' => 'Test medicine',
            'item_type' => 'medicine',
            'dispensing_method' => 'FEFO',
            'is_taxable' => true,
            'is_active' => true,
        ]);
        MedicineUnit::query()->create([
            'medicine_id' => $medicine->id,
            'unit_id' => $unit->id,
            'conversion_factor' => 1,
            'selling_price' => 100,
            'is_base_unit' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
        ]);
        $batch = Batch::query()->create([
            'branch_id' => $branch->id,
            'medicine_id' => $medicine->id,
            'batch_number' => 'B-001',
            'expiry_date' => today()->addYear(),
            'received_date' => today(),
            'initial_quantity_base' => 10,
            'remaining_quantity_base' => 10,
            'unit_purchase_price' => 30,
            'landed_cost_price' => 40,
            'is_active' => true,
        ]);
        $patient = Contact::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Test patient',
            'is_customer' => true,
            'is_active' => true,
        ]);
        $insurer = Contact::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Test insurer',
            'is_insurance_provider' => true,
            'is_active' => true,
        ]);
        $plan = InsurancePlan::query()->create([
            'insurer_contact_id' => $insurer->id,
            'name' => 'Standard plan',
            'code' => 'STD',
            'coverage_percentage' => 80,
            'patient_copay_percentage' => 20,
            'is_active' => true,
        ]);
        $policy = InsurancePolicy::query()->create([
            'patient_contact_id' => $patient->id,
            'insurance_plan_id' => $plan->id,
            'policy_number' => 'POL-001',
            'valid_from' => today(),
            'is_active' => true,
        ]);
        $session = PosSession::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'treasury_id' => $treasury->id,
            'opened_at' => now(),
            'opening_cash' => 0,
            'expected_cash' => 0,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->postJson('/pharmacy/sales', [
            'pos_session_id' => $session->id,
            'customer_id' => $patient->id,
            'insurance_policy_id' => $policy->id,
            'items' => [[
                'medicine_id' => $medicine->id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'discount_amount' => 10,
                'discount_reason' => 'Promotion',
            ]],
            'payments' => [[
                'payment_method' => 'cash',
                'amount' => 20.7,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.patient_due', '20.7000')
            ->assertJsonPath('data.insurance_due', '82.8000');

        $this->assertSame(9, $batch->refresh()->remaining_quantity_base);
        $this->assertSame('pending', InsuranceClaim::query()->firstOrFail()->status);
        $this->assertSame(82.8, (float) InsuranceClaim::query()->firstOrFail()->claimed_amount);

        $saleData = $response->json('data');
        $returnResponse = $this->actingAs($user)->postJson('/pharmacy/sale-returns', [
            'sale_id' => $saleData['id'],
            'pos_session_id' => $session->id,
            'treasury_id' => $treasury->id,
            'reason' => 'Patient returned the medicine',
            'items' => [[
                'sale_item_id' => $saleData['items'][0]['id'],
                'quantity' => 1,
            ]],
        ]);
        $returnResponse->assertCreated()
            ->assertJsonPath('data.patient_refund_amount', '20.7000')
            ->assertJsonPath('data.insurance_refund_amount', '82.8000');

        $this->assertSame(10, $batch->refresh()->remaining_quantity_base);
        $this->assertSame('rejected', InsuranceClaim::query()->firstOrFail()->status);
        $this->assertSame(0.0, (float) InsuranceClaim::query()->firstOrFail()->claimed_amount);
        $this->assertSame('returned', \App\Models\Sale::query()->firstOrFail()->status);
        $this->assertSame(0.0, (float) $treasury->refresh()->current_balance);

        $entry = JournalEntry::query()->with('lines')->firstOrFail();
        $this->assertSame((float) $entry->total_debit, (float) $entry->total_credit);
        $this->assertSame(2, JournalEntry::query()->count());
        foreach (JournalEntry::query()->get() as $journalEntry) {
            $this->assertSame((float) $journalEntry->total_debit, (float) $journalEntry->total_credit);
        }
    }

    public function test_card_payment_is_credited_to_its_selected_network_not_the_cash_drawer(): void
    {
            $user = User::query()->where('role', 'admin')->firstOrFail();
            $user->forceFill(['email_verified_at' => now()])->save();
            $branch = $user->branch;
            $drawer = Treasury::query()->where('branch_id', $branch->id)->where('type', 'cash_drawer')->firstOrFail();
            $network = Treasury::query()->where('branch_id', $branch->id)->where('type', 'bank')->firstOrFail();
            \App\Models\Setting::query()->firstOrFail()->update(['tax_rate' => 0]);

            $medicine = Medicine::query()->create([
                'category_id' => Category::query()->firstOrFail()->id,
                'dosage_form_id' => DosageForm::query()->firstOrFail()->id,
                'trade_name' => 'Network payment medicine',
                'item_type' => 'medicine',
                'dispensing_method' => 'FEFO',
                'is_taxable' => true,
                'is_active' => true,
            ]);
            $unit = Unit::query()->firstOrFail();
            MedicineUnit::query()->create([
                'medicine_id' => $medicine->id,
                'unit_id' => $unit->id,
                'conversion_factor' => 1,
                'selling_price' => 100,
                'is_base_unit' => true,
                'is_purchasable' => true,
                'is_sellable' => true,
            ]);
            Batch::query()->create([
                'branch_id' => $branch->id,
                'medicine_id' => $medicine->id,
                'batch_number' => 'NET-001',
                'expiry_date' => today()->addYear(),
                'received_date' => today(),
                'initial_quantity_base' => 5,
                'remaining_quantity_base' => 5,
                'unit_purchase_price' => 20,
                'landed_cost_price' => 20,
                'is_active' => true,
            ]);
            $session = PosSession::query()->create([
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'treasury_id' => $drawer->id,
                'opened_at' => now(),
                'opening_cash' => 0,
                'expected_cash' => 0,
                'status' => 'open',
            ]);

            $response = $this->actingAs($user)->postJson('/pharmacy/sales', [
                'pos_session_id' => $session->id,
                'items' => [['medicine_id' => $medicine->id, 'unit_id' => $unit->id, 'quantity' => 1]],
                'payments' => [[
                    'payment_method' => 'card',
                    'amount' => 100,
                    'treasury_id' => $network->id,
                ]],
            ])->assertCreated();

            $this->assertDatabaseHas('sale_payments', [
                'sale_id' => $response->json('data.id'),
                'treasury_id' => $network->id,
                'payment_method' => 'card',
                'amount' => '100.0000',
            ]);
            $this->assertSame(100.0, (float) $network->refresh()->current_balance);
            $this->assertSame(0.0, (float) $drawer->refresh()->current_balance);
            $this->assertSame(0.0, (float) $session->refresh()->expected_cash);
            $this->assertSame(100.0, (float) SalePayment::query()->where('payment_method', 'card')->sum('amount'));
    }
}
