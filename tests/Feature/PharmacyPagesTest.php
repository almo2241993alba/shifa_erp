<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\FinancialTransaction;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_only_their_cashier_report_page(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->withSession(['locale' => 'ar'])
            ->get('/pharmacy/cashier/reports')
            ->assertOk()
            ->assertSee('تقارير الكاشير');

        $this->get('/pharmacy/reports')->assertForbidden();
    }

    public function test_cashier_report_does_not_show_another_cashiers_sales(): void
    {
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
        $firstCashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);
        $otherCashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);
        $this->createSale($branch, $firstCashier, 'INV-CASHIER-1');
        $this->createSale($branch, $otherCashier, 'INV-CASHIER-2');

        $this->actingAs($firstCashier)
            ->get('/pharmacy/cashier/reports?from_date='.today()->toDateString().'&to_date='.today()->toDateString())
            ->assertOk()
            ->assertSee('INV-CASHIER-1')
            ->assertDontSee('INV-CASHIER-2');
    }

    public function test_admin_sales_report_includes_all_cashiers_sales_in_their_branch(): void
    {
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
        $admin = User::factory()->create(['role' => 'admin', 'branch_id' => $branch->id]);
        $firstCashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);
        $otherCashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);
        $this->createSale($branch, $firstCashier, 'INV-ADMIN-1');
        $this->createSale($branch, $otherCashier, 'INV-ADMIN-2');

        $this->actingAs($admin)
            ->get('/pharmacy/reports?from_date='.today()->toDateString().'&to_date='.today()->toDateString())
            ->assertOk()
            ->assertSee('INV-ADMIN-1')
            ->assertSee('INV-ADMIN-2')
            ->assertSee($firstCashier->name)
            ->assertSee($otherCashier->name);
    }

    public function test_report_text_and_direction_follow_the_selected_locale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/pharmacy/reports')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Sales reports')
            ->assertSee('Performance analysis')
            ->assertDontSee('تحليل الأداء');

        $this->withSession(['locale' => 'ar'])
            ->get('/pharmacy/reports')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('تقارير المبيعات')
            ->assertSee('تحليل الأداء');
    }

    public function test_guest_pages_render_in_the_selected_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/login')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Sign in')
            ->assertDontSee('تسجيل الدخول');

        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Smart pharmacy management')
            ->assertDontSee('إدارة الصيدلية الذكية');

        $this->withSession(['locale' => 'ar'])
            ->get('/login')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('تسجيل الدخول');

        $this->withSession(['locale' => 'ar'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('إدارة الصيدلية الذكية');
    }

    public function test_management_page_metadata_is_translated_with_english_keys(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $englishResponse = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/pharmacy/manage/medicines')
            ->assertOk()
            ->assertSee('Medicines and inventory')
            ->assertSee('Medicine name')
            ->assertDontSee('اسم الدواء');
        $englishConfig = $this->managementPageConfig($englishResponse->getContent());
        $this->assertSame('Medicine name', $englishConfig['columns'][0]['label']);

        $arabicResponse = $this->withSession(['locale' => 'ar'])
            ->get('/pharmacy/manage/medicines')
            ->assertOk()
            ->assertSee('الأدوية والمخزون');
        $arabicConfig = $this->managementPageConfig($arabicResponse->getContent());
        $this->assertSame('اسم الدواء', $arabicConfig['columns'][0]['label']);
    }

    public function test_admin_can_create_an_employee_from_the_wizard_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get('/pharmacy/employees/page')
            ->assertOk()
            ->assertSee('data-wizard', false)
            ->assertSee('الموظفون المسجلون');

        $this->post('/pharmacy/employees/page', [
            'branch_id' => $branch->id,
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'employee_number' => 'EMP-TEST-1',
            'national_id' => 'NID-TEST-1',
            'job_title' => 'cashier',
            'salary_type' => 'monthly',
            'salary_basic' => 1000,
            'hire_date' => '2026-01-01',
        ])->assertRedirect(route('pharmacy.employees.page'));

        $contact = Contact::where('email', 'employee@example.com')->firstOrFail();
        $this->assertTrue($contact->is_employee);
        $this->assertDatabaseHas('employees', [
            'contact_id' => $contact->id,
            'employee_number' => 'EMP-TEST-1',
            'branch_id' => $branch->id,
        ]);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_employee_form_validates_required_fields_on_the_server(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/pharmacy/employees/page', [])
            ->assertSessionHasErrors([
                'branch_id',
                'name',
                'employee_number',
                'national_id',
                'job_title',
                'salary_type',
                'salary_basic',
                'hire_date',
            ]);

        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_cashier_cannot_open_employee_or_payroll_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->get('/pharmacy/employees/page')
            ->assertForbidden();

        $this->get('/pharmacy/payroll')->assertForbidden();
    }

    public function test_admin_can_open_the_payroll_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get('/pharmacy/payroll')
            ->assertOk()
            ->assertSee('إنشاء مسير الراتب');
    }

    public function test_payroll_form_validates_required_fields_before_processing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/pharmacy/payroll', [])
            ->assertSessionHasErrors(['employee_id', 'year_month_key']);

        $this->assertDatabaseCount('salary_slips', 0);
        $this->assertDatabaseCount('financial_transactions', 0);
    }

    private function createSale(Branch $branch, User $cashier, string $invoiceNumber): void
    {
        $account = Account::query()->create([
            'code' => 'CASH-'.str_replace('-', '', $invoiceNumber),
            'name' => 'Cash account',
            'account_type' => 'asset',
            'is_transactional' => true,
        ]);
        $treasury = Treasury::query()->create([
            'branch_id' => $branch->id,
            'account_id' => $account->id,
            'name' => 'Cash drawer '.$invoiceNumber,
            'type' => 'cash_drawer',
        ]);
        $session = PosSession::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'treasury_id' => $treasury->id,
            'opened_at' => now(),
            'status' => 'open',
        ]);
        $transaction = FinancialTransaction::query()->create();

        Sale::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $branch->id,
            'pos_session_id' => $session->id,
            'user_id' => $cashier->id,
            'invoice_number' => $invoiceNumber,
            'session_receipt_number' => 1,
            'subtotal' => 100,
            'grand_total' => 100,
            'paid_amount' => 100,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
    }

    private function managementPageConfig(string $html): array
    {
        preg_match('/data-config="([^"]*)"/', $html, $matches);

        return json_decode(
            html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
