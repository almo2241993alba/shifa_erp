<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class ManagementPageController extends Controller
{
    public function show(Request $request, string $resource)
    {
        $pages = $this->pages($request);
        abort_unless(isset($pages[$resource]), 404);

        $page = $pages[$resource];
        abort_unless(in_array($request->user()->role, $page['roles'], true), 403);
        $page = $this->translatePage($page);
        $page['writable'] = $page['writable']
            && in_array($request->user()->role, $page['write_roles'] ?? $page['roles'], true);
        $page['deletable'] = $page['deletable'] ?? $page['writable'];

        return view('pharmacy.resource', [
            'page' => $page + ['key' => $resource],
            'currency' => Setting::query()->value('currency_code') ?: 'SDG',
        ]);
    }

    private function translatePage(array $page): array
    {
        foreach ($page as $key => $value) {
            if (in_array($key, ['title', 'description', 'label'], true) && is_string($value)) {
                $page[$key] = __($value);
            } elseif ($key === 'options' && is_array($value)) {
                $page[$key] = array_map(fn (string $label) => __($label), $value);
            } elseif (is_array($value)) {
                $page[$key] = $this->translatePage($value);
            }
        }

        return $page;
    }

    private function pages(Request $request): array
    {
        $user = $request->user();
        $branchField = $user->branch_id === null
            ? [
                'name' => 'branch_id',
                'label' => 'Branch',
                'type' => 'select',
                'required' => true,
                'options_endpoint' => '/pharmacy/branches?per_page=100',
                'option_value' => 'id',
                'option_label' => 'name',
            ]
            : [
                'name' => 'branch_id',
                'type' => 'hidden',
                'default' => $user->branch_id,
            ];

        return [
            'medicines' => [
                'title' => 'Medicines and inventory',
                'description' => 'Manage products, categories, and core sales details.',
                'endpoint' => '/pharmacy/medicines',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'trade_name', 'label' => 'Medicine name'],
                    ['key' => 'scientific_name', 'label' => 'Scientific name'],
                    ['key' => 'category.name', 'label' => 'Category'],
                    ['key' => 'barcode', 'label' => 'Barcode'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'trade_name', 'label' => 'Trade name', 'type' => 'text', 'required' => true],
                    ['name' => 'scientific_name', 'label' => 'Scientific name', 'type' => 'text'],
                    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/categories?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'dosage_form_id', 'label' => 'Dosage form', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/dosage-forms?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'barcode', 'label' => 'Barcode', 'type' => 'text'],
                    ['name' => 'manufacturer', 'label' => 'Manufacturer', 'type' => 'text'],
                    ['name' => 'item_type', 'label' => 'Item type', 'type' => 'select', 'options' => ['medicine' => 'Medicine', 'medical_supply' => 'Medical supply', 'cosmetic' => 'Cosmetic']],
                    ['name' => 'dispensing_method', 'label' => 'Dispensing method', 'type' => 'select', 'options' => ['FEFO' => 'First to expire, first out', 'FIFO' => 'First in, first out']],
                    ['name' => 'min_stock_alert', 'label' => 'Stock alert threshold', 'type' => 'number', 'min' => 0, 'default' => 0],
                    ['name' => 'is_taxable', 'label' => 'Taxable', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'categories' => [
                'title' => 'Medicine categories',
                'description' => 'Organize medicines and supplies into clear groups.',
                'endpoint' => '/pharmacy/categories',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Category name'],
                    ['key' => 'description', 'label' => 'Description'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Category name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'dosage-forms' => [
                'title' => 'Dosage forms',
                'description' => 'Manage dosage forms used in the medicine catalog.',
                'endpoint' => '/pharmacy/dosage-forms',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Dosage form'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Dosage form', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'units' => [
                'title' => 'Units',
                'description' => 'Configure the selling and purchasing units used for items.',
                'endpoint' => '/pharmacy/units',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Unit'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Unit name', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'contacts' => [
                'title' => 'Customers and suppliers',
                'description' => 'Manage customer, supplier, patient, and insurance company details.',
                'endpoint' => '/pharmacy/contacts',
                'roles' => ['admin', 'pharmacist', 'accountant', 'cashier'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'email', 'label' => 'Email'],
                    ['key' => 'contact_type', 'label' => 'Account type'],
                    ['key' => 'is_customer', 'label' => 'Customer', 'format' => 'boolean'],
                    ['key' => 'is_supplier', 'label' => 'Supplier', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => array_values(array_filter([
                    $branchField,
                    ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                    ['name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text'],
                    ['name' => 'contact_type', 'label' => 'Account type', 'type' => 'select', 'options' => ['B2C' => 'Individual', 'B2B' => 'Company']],
                    ['name' => 'is_customer', 'label' => 'Customer / patient', 'type' => 'checkbox', 'default' => true],
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_supplier', 'label' => 'Supplier', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_employee', 'label' => 'Employee', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_insurance_provider', 'label' => 'Insurance provider', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'credit_limit', 'label' => 'Credit limit', 'type' => 'number', 'min' => 0] : null,
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ])),
            ],
            'branches' => [
                'title' => 'Branches',
                'description' => 'Manage current branches and prepare for future expansion.',
                'endpoint' => '/pharmacy/branches',
                'roles' => ['admin'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Branch name'],
                    ['key' => 'code', 'label' => 'Code'],
                    ['key' => 'phone', 'label' => 'Phone'],
                    ['key' => 'is_main', 'label' => 'Main branch', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Branch name', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Branch code', 'type' => 'text', 'required' => true],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                    ['name' => 'tax_number', 'label' => 'Tax number', 'type' => 'text'],
                    ['name' => 'is_main', 'label' => 'Main branch', 'type' => 'checkbox'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'accounts' => [
                'title' => 'Chart of accounts',
                'description' => 'Review accounts and classifications in the chart of accounts.',
                'endpoint' => '/pharmacy/accounts',
                'roles' => ['admin'],
                'writable' => true,
                'columns' => [
                    ['key' => 'code', 'label' => 'Account code'],
                    ['key' => 'name', 'label' => 'Account name'],
                    ['key' => 'account_type', 'label' => 'Category'],
                    ['key' => 'is_transactional', 'label' => 'Transaction account', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Account code', 'type' => 'text', 'required' => true],
                    ['name' => 'name', 'label' => 'Account name', 'type' => 'text', 'required' => true],
                    ['name' => 'account_type', 'label' => 'Account type', 'type' => 'select', 'required' => true, 'options' => ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses']],
                    ['name' => 'parent_id', 'label' => 'Parent account (optional)', 'type' => 'text'],
                    ['name' => 'is_transactional', 'label' => 'Transaction account', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'treasuries' => [
                'title' => 'Treasuries and payment methods',
                'description' => 'Manage payment methods and set a fixed reserve for each cash drawer.',
                'endpoint' => '/pharmacy/treasuries',
                'roles' => ['admin', 'accountant'],
                'write_roles' => ['admin'],
                'writable' => true,
                'deletable' => false,
                'columns' => [
                    ['key' => 'name', 'label' => 'Treasury name'],
                    ['key' => 'branch.name', 'label' => 'Branch'],
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'current_balance', 'label' => 'Balance', 'format' => 'money'],
                    ['key' => 'drawer_reserve_amount', 'label' => 'Fixed reserve', 'format' => 'money'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/branches?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'account_id', 'label' => 'Financial account', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/accounts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'name', 'label' => 'Treasury or network name', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['cash_drawer' => 'Cash drawer', 'main_safe' => 'main_safe', 'bank' => 'bank']],
                    ['name' => 'drawer_reserve_amount', 'label' => 'Drawer reserve', 'type' => 'number', 'min' => 0, 'step' => '0.01', 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'sales' => [
                'title' => 'Sales',
                'description' => 'Review invoices, collections, and insurance amounts.',
                'endpoint' => '/pharmacy/sales',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'invoice_number', 'label' => 'Invoice number'],
                    ['key' => 'customer.name', 'label' => 'Customer'],
                    ['key' => 'grand_total', 'label' => 'Total', 'format' => 'money'],
                    ['key' => 'paid_amount', 'label' => 'Paid amount', 'format' => 'money'],
                    ['key' => 'insurance_due', 'label' => 'Insurance', 'format' => 'money'],
                    ['key' => 'created_at', 'label' => 'Date', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'purchases' => [
                'title' => 'Purchases',
                'description' => 'Review purchase invoices and payment status.',
                'endpoint' => '/pharmacy/purchases',
                'roles' => ['admin', 'storekeeper', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'purchase_number', 'label' => 'Purchase number'],
                    ['key' => 'supplier.name', 'label' => 'Supplier'],
                    ['key' => 'grand_total', 'label' => 'Total', 'format' => 'money'],
                    ['key' => 'remaining_amount', 'label' => 'Remaining', 'format' => 'money'],
                    ['key' => 'payment_status', 'label' => 'Payment', 'format' => 'status'],
                    ['key' => 'purchase_date', 'label' => 'Date', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'expenses' => [
                'title' => 'Expenses',
                'description' => 'Review expenses by type and treasury.',
                'endpoint' => '/pharmacy/expenses',
                'roles' => ['admin', 'accountant', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'expense_type.type_name', 'label' => 'Expense type'],
                    ['key' => 'amount', 'label' => 'Amount', 'format' => 'money'],
                    ['key' => 'treasury.name', 'label' => 'Treasury'],
                    ['key' => 'expense_date', 'label' => 'Date', 'format' => 'date'],
                    ['key' => 'user.name', 'label' => 'Recorded by'],
                ],
                'fields' => [],
            ],
            'batches' => [
                'title' => 'Inventory batches',
                'description' => 'Monitor batch balances and expiry dates.',
                'endpoint' => '/pharmacy/batches',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => false,
                'columns' => [
                    ['key' => 'medicine.trade_name', 'label' => 'Item'],
                    ['key' => 'batch_number', 'label' => 'Batch number'],
                    ['key' => 'remaining_quantity_base', 'label' => 'Remaining'],
                    ['key' => 'expiry_date', 'label' => 'Role', 'format' => 'date'],
                    ['key' => 'branch.name', 'label' => 'Branch'],
                ],
                'fields' => [],
            ],
            'insurance-claims' => [
                'title' => 'Insurance claims',
                'description' => 'Review insurance claims, approval, and collection statuses.',
                'endpoint' => '/pharmacy/insurance-claims',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'claim_number', 'label' => 'Claim number'],
                    ['key' => 'patient.name', 'label' => 'Patient'],
                    ['key' => 'insurer.name', 'label' => 'Insurance company'],
                    ['key' => 'claimed_amount', 'label' => 'Claimed amount', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
                    ['key' => 'submitted_at', 'label' => 'Submission date', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'journal-entries' => [
                'title' => 'Journal entries',
                'description' => 'Review automated and manual journal entries and their balance status.',
                'endpoint' => '/pharmacy/journal-entries',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'entry_number', 'label' => 'Journal entry number'],
                    ['key' => 'description', 'label' => 'Statement'],
                    ['key' => 'entry_date', 'label' => 'Date', 'format' => 'date'],
                    ['key' => 'total_debit', 'label' => 'Debit', 'format' => 'money'],
                    ['key' => 'total_credit', 'label' => 'Credit', 'format' => 'money'],
                ],
                'fields' => [],
            ],
            'financial-transactions' => [
                'title' => 'Transaction register',
                'description' => 'A unified record of financial transactions linked to invoices and journal entries.',
                'endpoint' => '/pharmacy/financial-transactions',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'id', 'label' => 'Transaction number'],
                    ['key' => 'created_at', 'label' => 'Date', 'format' => 'date'],
                    ['key' => 'journal_entry.entry_number', 'label' => 'Journal entry'],
                ],
                'fields' => [],
            ],
            'sale-returns' => [
                'title' => 'Sales returns',
                'description' => 'Review refunded amounts and point-of-sale return activity.',
                'endpoint' => '/pharmacy/sale-returns',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'return_number', 'label' => 'Return number'],
                    ['key' => 'sale.invoice_number', 'label' => 'Original invoice'],
                    ['key' => 'patient_refund_amount', 'label' => 'Customer amount', 'format' => 'money'],
                    ['key' => 'insurance_refund_amount', 'label' => 'Insurance amount', 'format' => 'money'],
                    ['key' => 'reason', 'label' => 'Reason'],
                    ['key' => 'created_at', 'label' => 'Date', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'purchase-returns' => [
                'title' => 'Purchase returns',
                'description' => 'Review items returned to suppliers and their value.',
                'endpoint' => '/pharmacy/purchase-returns',
                'roles' => ['admin', 'storekeeper', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'return_number', 'label' => 'Return number'],
                    ['key' => 'supplier.name', 'label' => 'Supplier'],
                    ['key' => 'total_return_amount', 'label' => 'Return amount', 'format' => 'money'],
                    ['key' => 'return_date', 'label' => 'Date', 'format' => 'date'],
                    ['key' => 'reason', 'label' => 'Reason'],
                ],
                'fields' => [],
            ],
            'pos-sessions' => [
                'title' => 'Cashier sessions',
                'description' => 'Review open and closed shifts and drawer variances.',
                'endpoint' => '/pharmacy/pos-sessions',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'user.name', 'label' => 'Cashier'],
                    ['key' => 'treasury.name', 'label' => 'Treasury'],
                    ['key' => 'opened_at', 'label' => 'Opened at', 'format' => 'date'],
                    ['key' => 'expected_cash', 'label' => 'Expected cash', 'format' => 'money'],
                    ['key' => 'difference', 'label' => 'Difference', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [],
            ],
            'insurance-plans' => [
                'title' => 'Insurance plans',
                'description' => 'View coverage plans, co-pay rates, and claim limits.',
                'endpoint' => '/pharmacy/insurance-plans',
                'roles' => ['admin', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'Plan name'],
                    ['key' => 'code', 'label' => 'Plan code'],
                    ['key' => 'insurer.name', 'label' => 'Insurance company'],
                    ['key' => 'coverage_percentage', 'label' => 'Coverage %'],
                    ['key' => 'patient_copay_percentage', 'label' => 'Patient share %'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'insurer_contact_id', 'label' => 'Insurance company', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/contacts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'name', 'label' => 'Plan name', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Plan code', 'type' => 'text', 'required' => true],
                    ['name' => 'coverage_percentage', 'label' => 'Coverage percentage', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
                    ['name' => 'patient_copay_percentage', 'label' => 'Patient share %', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
                    ['name' => 'max_coverage_per_claim', 'label' => 'Maximum claim amount', 'type' => 'number', 'min' => 0],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'insurance-policies' => [
                'title' => 'Insurance policies',
                'description' => 'Manage medical policies, membership numbers, and coverage periods.',
                'endpoint' => '/pharmacy/insurance-policies',
                'roles' => ['admin', 'pharmacist', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'policy_number', 'label' => 'Policy number'],
                    ['key' => 'patient.name', 'label' => 'Patient'],
                    ['key' => 'plan.name', 'label' => 'Plan'],
                    ['key' => 'valid_from', 'label' => 'Coverage start', 'format' => 'date'],
                    ['key' => 'valid_until', 'label' => 'Coverage end', 'format' => 'date'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'patient_contact_id', 'label' => 'Patient', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/contacts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'insurance_plan_id', 'label' => 'Insurance plan', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/insurance-plans?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'policy_number', 'label' => 'Policy number', 'type' => 'text', 'required' => true],
                    ['name' => 'member_number', 'label' => 'Member number', 'type' => 'text'],
                    ['name' => 'valid_from', 'label' => 'Valid from', 'type' => 'date', 'required' => true],
                    ['name' => 'valid_until', 'label' => 'Valid until', 'type' => 'date'],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'expense-types' => [
                'title' => 'Expense types',
                'description' => 'Classify expenses and link them to an appropriate account.',
                'endpoint' => '/pharmacy/expense-types',
                'roles' => ['admin', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'type_name', 'label' => 'Expense type'],
                    ['key' => 'expense_category', 'label' => 'Category'],
                    ['key' => 'account.code', 'label' => 'Account code'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'type_name', 'label' => 'Expense type', 'type' => 'text', 'required' => true],
                    ['name' => 'account_id', 'label' => 'Expense account ID', 'type' => 'number', 'required' => true, 'min' => 1],
                    ['name' => 'expense_category', 'label' => 'Category', 'type' => 'select', 'options' => ['operational' => 'Operational', 'administrative' => 'Administrative', 'marketing' => 'Marketing', 'maintenance' => 'Maintenance']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'salary-slips' => [
                'title' => 'Payroll slips',
                'description' => 'Review payroll entitlements, payments, and balances.',
                'endpoint' => '/pharmacy/salary-slips',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'employee.contact.name', 'label' => 'Employee'],
                    ['key' => 'year_month_key', 'label' => 'Period'],
                    ['key' => 'gross_deserved_amount', 'label' => 'Due', 'format' => 'money'],
                    ['key' => 'actual_paid_amount', 'label' => 'Paid amount', 'format' => 'money'],
                    ['key' => 'balance_remaining', 'label' => 'Remaining', 'format' => 'money'],
                    ['key' => 'payout_status', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [],
            ],
            'employee-work-logs' => [
                'title' => 'Work logs',
                'description' => 'Track work hours, shifts, and overtime.',
                'endpoint' => '/pharmacy/employee-work-logs',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'employee.contact.name', 'label' => 'Employee'],
                    ['key' => 'work_date', 'label' => 'Date', 'format' => 'date'],
                    ['key' => 'worked_shifts', 'label' => 'Shifts'],
                    ['key' => 'hours_worked', 'label' => 'Hours worked'],
                    ['key' => 'overtime_hours', 'label' => 'Overtime hours'],
                    ['key' => 'drawer_discrepancy', 'label' => 'Drawer variance', 'format' => 'money'],
                ],
                'fields' => [],
            ],
            'employees' => [
                'title' => 'Employees',
                'description' => 'Manage employee records linked to contacts.',
                'endpoint' => '/pharmacy/employees',
                'roles' => ['admin'],
                'writable' => false,
                'columns' => [
                    ['key' => 'contact.name', 'label' => 'Employee name'],
                    ['key' => 'branch.name', 'label' => 'Branch'],
                    ['key' => 'job_title', 'label' => 'Job title'],
                    ['key' => 'is_active', 'label' => 'Status', 'format' => 'status'],
                ],
                'fields' => [],
            ],
        ];
    }
}
