<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashierPageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactPhoneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosageFormController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePageController;
use App\Http\Controllers\EmployeeWorkLogController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\InsurancePlanController;
use App\Http\Controllers\InsurancePlanMedicineController;
use App\Http\Controllers\InsurancePolicyController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\ManagementPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MedicineAccessoryController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MedicineUnitController;
use App\Http\Controllers\PayrollPageController;
use App\Http\Controllers\PosSessionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RegistrationRequestController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalarySlipController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::post('locale', LocaleController::class)->name('locale.switch');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'active'])
    ->prefix('pharmacy')
    ->name('pharmacy.')
    ->group(function (): void {
        Route::get('admin', DashboardController::class)
            ->middleware('role:admin,pharmacist,accountant,storekeeper')
            ->name('admin');
        Route::get('cashier', CashierPageController::class)
            ->middleware('role:admin,pharmacist,cashier')
            ->name('cashier');
        Route::get('cashier/products', [CashierPageController::class, 'products'])
            ->middleware('role:admin,pharmacist,cashier')
            ->name('cashier.products');
        Route::get('cashier/reports', [ReportsController::class, 'cashier'])
            ->middleware('role:admin,cashier')
            ->name('cashier.reports');
        Route::get('reports', [ReportsController::class, 'index'])
            ->middleware('role:admin,pharmacist,accountant')
            ->name('reports.index');
        Route::get('employees/page', [EmployeePageController::class, 'index'])
            ->middleware('role:admin')
            ->name('employees.page');
        Route::post('employees/page', [EmployeePageController::class, 'store'])
            ->middleware('role:admin')
            ->name('employees.store-page');
        Route::get('payroll', [PayrollPageController::class, 'index'])
            ->middleware('role:admin,accountant')
            ->name('payroll.index');
        Route::post('payroll', [PayrollPageController::class, 'store'])
            ->middleware('role:admin,accountant')
            ->name('payroll.store');
        Route::get('manage/{resource}', [ManagementPageController::class, 'show'])
            ->middleware('role:admin,pharmacist,cashier,accountant,storekeeper')
            ->name('manage');

        Route::middleware('role:admin')->group(function (): void {
            Route::get('registration-requests', [RegistrationRequestController::class, 'index'])
                ->name('registration-requests.index');
            Route::post('registration-requests/{user}/approve', [RegistrationRequestController::class, 'approve'])
                ->name('registration-requests.approve');
            Route::apiResource('branches', BranchController::class);
            Route::apiResource('accounts', AccountController::class);
            Route::apiResource('employees', EmployeeController::class);
            Route::get('settings', [SettingController::class, 'show'])->name('settings.show');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::middleware('role:admin,pharmacist,storekeeper')->group(function (): void {
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('dosage-forms', DosageFormController::class);
            Route::apiResource('units', UnitController::class);
            Route::get('medicines/{medicine}', [MedicineController::class, 'show'])->name('medicines.show');
            Route::post('medicines', [MedicineController::class, 'store'])->name('medicines.store');
            Route::match(['put', 'patch'], 'medicines/{medicine}', [MedicineController::class, 'update'])
                ->name('medicines.update');
            Route::delete('medicines/{medicine}', [MedicineController::class, 'destroy'])->name('medicines.destroy');
            Route::apiResource('batches', BatchController::class)->only(['index', 'show']);
            Route::get('medicines/{medicine}/units', [MedicineUnitController::class, 'index'])->name('medicines.units.index');
            Route::post('medicines/{medicine}/units', [MedicineUnitController::class, 'store'])->name('medicines.units.store');
            Route::put('medicines/{medicine}/units/{medicineUnit}', [MedicineUnitController::class, 'update'])->name('medicines.units.update');
            Route::delete('medicines/{medicine}/units/{medicineUnit}', [MedicineUnitController::class, 'destroy'])->name('medicines.units.destroy');
            Route::get('medicines/{medicine}/accessories', [MedicineAccessoryController::class, 'index'])->name('medicines.accessories.index');
            Route::post('medicines/{medicine}/accessories', [MedicineAccessoryController::class, 'store'])->name('medicines.accessories.store');
            Route::put('medicines/{medicine}/accessories/{medicineAccessory}', [MedicineAccessoryController::class, 'update'])->name('medicines.accessories.update');
            Route::delete('medicines/{medicine}/accessories/{medicineAccessory}', [MedicineAccessoryController::class, 'destroy'])->name('medicines.accessories.destroy');
        });

        Route::get('medicines', [MedicineController::class, 'index'])
            ->middleware('role:admin,pharmacist,storekeeper,cashier')
            ->name('medicines.index');

        Route::middleware('role:admin,pharmacist,cashier,accountant')->group(function (): void {
            Route::apiResource('contacts', ContactController::class);
            Route::get('contacts/{contact}/phones', [ContactPhoneController::class, 'index'])->name('contacts.phones.index');
            Route::post('contacts/{contact}/phones', [ContactPhoneController::class, 'store'])->name('contacts.phones.store');
            Route::put('contacts/{contact}/phones/{phone}', [ContactPhoneController::class, 'update'])->name('contacts.phones.update');
            Route::delete('contacts/{contact}/phones/{phone}', [ContactPhoneController::class, 'destroy'])->name('contacts.phones.destroy');
        });

        Route::middleware('role:admin,accountant,pharmacist')->group(function (): void {
            Route::apiResource('insurance-policies', InsurancePolicyController::class);
        });

        Route::middleware('role:admin,accountant')->group(function (): void {
            Route::apiResource('treasuries', TreasuryController::class);
            Route::apiResource('expense-types', ExpenseTypeController::class);
            Route::apiResource('insurance-plans', InsurancePlanController::class);
            Route::apiResource('employee-work-logs', EmployeeWorkLogController::class);
            Route::apiResource('salary-slips', SalarySlipController::class)->only(['index', 'store', 'show']);
            Route::apiResource('journal-entries', JournalEntryController::class)->only(['index', 'store', 'show']);
            Route::apiResource('financial-transactions', FinancialTransactionController::class)->only(['index', 'show']);
            Route::get('insurance-plans/{plan}/medicines', [InsurancePlanMedicineController::class, 'index'])
                ->name('insurance-plans.medicines.index');
            Route::post('insurance-plans/{plan}/medicines', [InsurancePlanMedicineController::class, 'store'])
                ->name('insurance-plans.medicines.store');
            Route::put('insurance-plans/{plan}/medicines/{medicineId}', [InsurancePlanMedicineController::class, 'update'])
                ->name('insurance-plans.medicines.update');
            Route::delete('insurance-plans/{plan}/medicines/{medicineId}', [InsurancePlanMedicineController::class, 'destroy'])
                ->name('insurance-plans.medicines.destroy');
            Route::get('insurance-claims', [InsuranceClaimController::class, 'index'])->name('insurance-claims.index');
            Route::get('insurance-claims/{claim}', [InsuranceClaimController::class, 'show'])->name('insurance-claims.show');
            Route::post('insurance-claims/{claim}/decide', [InsuranceClaimController::class, 'decide'])->name('insurance-claims.decide');
            Route::post('insurance-claims/{claim}/record-payment', [InsuranceClaimController::class, 'recordPayment'])->name('insurance-claims.record-payment');
        });

        Route::middleware('role:admin,pharmacist,cashier')->group(function (): void {
            Route::get('pos-sessions', [PosSessionController::class, 'index'])->name('pos-sessions.index');
            Route::post('pos-sessions/open', [PosSessionController::class, 'open'])->name('pos-sessions.open');
            Route::post('pos-sessions/{session}/close', [PosSessionController::class, 'close'])->name('pos-sessions.close');
            Route::post('sales/quote', [SaleController::class, 'quote'])->name('sales.quote');
            Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
            Route::apiResource('sale-returns', SaleReturnController::class)->only(['index', 'store', 'show']);
        });

        Route::middleware('role:admin,storekeeper,accountant')->group(function (): void {
            Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show']);
            Route::apiResource('purchase-returns', PurchaseReturnController::class)->only(['index', 'store', 'show']);
        });

        Route::middleware('role:admin,accountant,cashier')->group(function (): void {
            Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show']);
        });
    });

require __DIR__.'/auth.php';
