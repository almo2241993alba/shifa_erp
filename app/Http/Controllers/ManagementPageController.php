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
                'label' => 'الفرع',
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
                'title' => 'الأدوية والمخزون',
                'description' => 'إدارة الأصناف والتصنيفات وبيانات البيع الأساسية.',
                'endpoint' => '/pharmacy/medicines',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'trade_name', 'label' => 'اسم الدواء'],
                    ['key' => 'scientific_name', 'label' => 'الاسم العلمي'],
                    ['key' => 'category.name', 'label' => 'التصنيف'],
                    ['key' => 'barcode', 'label' => 'الباركود'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'trade_name', 'label' => 'الاسم التجاري', 'type' => 'text', 'required' => true],
                    ['name' => 'scientific_name', 'label' => 'الاسم العلمي', 'type' => 'text'],
                    ['name' => 'category_id', 'label' => 'التصنيف', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/categories?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'dosage_form_id', 'label' => 'الشكل الدوائي', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/dosage-forms?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'barcode', 'label' => 'الباركود', 'type' => 'text'],
                    ['name' => 'manufacturer', 'label' => 'الشركة المصنعة', 'type' => 'text'],
                    ['name' => 'item_type', 'label' => 'نوع الصنف', 'type' => 'select', 'options' => ['medicine' => 'دواء', 'medical_supply' => 'مستلزم طبي', 'cosmetic' => 'مستحضر']],
                    ['name' => 'dispensing_method', 'label' => 'طريقة الصرف', 'type' => 'select', 'options' => ['FEFO' => 'الأقرب انتهاءً أولًا', 'FIFO' => 'الأقدم استلامًا أولًا']],
                    ['name' => 'min_stock_alert', 'label' => 'حد تنبيه المخزون', 'type' => 'number', 'min' => 0, 'default' => 0],
                    ['name' => 'is_taxable', 'label' => 'خاضع للضريبة', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'categories' => [
                'title' => 'تصنيفات الأدوية',
                'description' => 'تنظيم الأدوية والمستلزمات في مجموعات واضحة.',
                'endpoint' => '/pharmacy/categories',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'اسم التصنيف'],
                    ['key' => 'description', 'label' => 'الوصف'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'اسم التصنيف', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'الوصف', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'dosage-forms' => [
                'title' => 'الأشكال الدوائية',
                'description' => 'إدارة أشكال الجرعات المستخدمة في دليل الأدوية.',
                'endpoint' => '/pharmacy/dosage-forms',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'الشكل الدوائي'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'الشكل الدوائي', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'units' => [
                'title' => 'وحدات القياس',
                'description' => 'إعداد وحدات البيع والشراء المستخدمة للأصناف.',
                'endpoint' => '/pharmacy/units',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'الوحدة'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'اسم الوحدة', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'contacts' => [
                'title' => 'العملاء والموردون',
                'description' => 'إدارة بيانات العملاء والموردين والمرضى وشركات التأمين.',
                'endpoint' => '/pharmacy/contacts',
                'roles' => ['admin', 'pharmacist', 'accountant', 'cashier'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'الاسم'],
                    ['key' => 'email', 'label' => 'البريد الإلكتروني'],
                    ['key' => 'contact_type', 'label' => 'نوع الحساب'],
                    ['key' => 'is_customer', 'label' => 'عميل', 'format' => 'boolean'],
                    ['key' => 'is_supplier', 'label' => 'مورد', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => array_values(array_filter([
                    $branchField,
                    ['name' => 'name', 'label' => 'الاسم الكامل', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'البريد الإلكتروني', 'type' => 'email'],
                    ['name' => 'address', 'label' => 'العنوان', 'type' => 'text'],
                    ['name' => 'tax_number', 'label' => 'الرقم الضريبي', 'type' => 'text'],
                    ['name' => 'contact_type', 'label' => 'نوع الحساب', 'type' => 'select', 'options' => ['B2C' => 'فرد', 'B2B' => 'شركة']],
                    ['name' => 'is_customer', 'label' => 'عميل / مريض', 'type' => 'checkbox', 'default' => true],
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_supplier', 'label' => 'مورد', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_employee', 'label' => 'موظف', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'is_insurance_provider', 'label' => 'شركة تأمين', 'type' => 'checkbox'] : null,
                    $user->hasAnyRole(['admin', 'accountant']) ? ['name' => 'credit_limit', 'label' => 'حد الائتمان', 'type' => 'number', 'min' => 0] : null,
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ])),
            ],
            'branches' => [
                'title' => 'الفروع',
                'description' => 'إدارة الفروع الحالية وتجهيز التوسع المستقبلي.',
                'endpoint' => '/pharmacy/branches',
                'roles' => ['admin'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'اسم الفرع'],
                    ['key' => 'code', 'label' => 'الرمز'],
                    ['key' => 'phone', 'label' => 'الهاتف'],
                    ['key' => 'is_main', 'label' => 'الفرع الرئيسي', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'اسم الفرع', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'رمز الفرع', 'type' => 'text', 'required' => true],
                    ['name' => 'phone', 'label' => 'الهاتف', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'العنوان', 'type' => 'text'],
                    ['name' => 'tax_number', 'label' => 'الرقم الضريبي', 'type' => 'text'],
                    ['name' => 'is_main', 'label' => 'فرع رئيسي', 'type' => 'checkbox'],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'accounts' => [
                'title' => 'دليل الحسابات',
                'description' => 'مراجعة الحسابات وتصنيفها ضمن شجرة الحسابات.',
                'endpoint' => '/pharmacy/accounts',
                'roles' => ['admin'],
                'writable' => true,
                'columns' => [
                    ['key' => 'code', 'label' => 'رمز الحساب'],
                    ['key' => 'name', 'label' => 'اسم الحساب'],
                    ['key' => 'account_type', 'label' => 'التصنيف'],
                    ['key' => 'is_transactional', 'label' => 'حساب حركة', 'format' => 'boolean'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'رمز الحساب', 'type' => 'text', 'required' => true],
                    ['name' => 'name', 'label' => 'اسم الحساب', 'type' => 'text', 'required' => true],
                    ['name' => 'account_type', 'label' => 'نوع الحساب', 'type' => 'select', 'required' => true, 'options' => ['asset' => 'أصول', 'liability' => 'التزامات', 'equity' => 'حقوق ملكية', 'revenue' => 'إيرادات', 'expense' => 'مصروفات']],
                    ['name' => 'parent_id', 'label' => 'الحساب الأب (اختياري)', 'type' => 'text'],
                    ['name' => 'is_transactional', 'label' => 'حساب حركة', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'treasuries' => [
                'title' => 'الخزائن ووسائل التحصيل',
                'description' => 'إدارة وسائل التحصيل وضبط العهدة الثابتة لكل درج نقدي.',
                'endpoint' => '/pharmacy/treasuries',
                'roles' => ['admin', 'accountant'],
                'write_roles' => ['admin'],
                'writable' => true,
                'deletable' => false,
                'columns' => [
                    ['key' => 'name', 'label' => 'اسم الخزينة'],
                    ['key' => 'branch.name', 'label' => 'الفرع'],
                    ['key' => 'type', 'label' => 'النوع'],
                    ['key' => 'current_balance', 'label' => 'الرصيد', 'format' => 'money'],
                    ['key' => 'drawer_reserve_amount', 'label' => 'العهدة الثابتة', 'format' => 'money'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'branch_id', 'label' => 'الفرع', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/branches?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'account_id', 'label' => 'الحساب المالي', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/accounts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'name', 'label' => 'اسم الخزينة أو الشبكة', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'النوع', 'type' => 'select', 'required' => true, 'options' => ['cash_drawer' => 'درج نقدي', 'main_safe' => 'خزينة رئيسية', 'bank' => 'بنك أو شبكة']],
                    ['name' => 'drawer_reserve_amount', 'label' => 'العهدة الثابتة للدرج', 'type' => 'number', 'min' => 0, 'step' => '0.01', 'default' => 0],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'sales' => [
                'title' => 'المبيعات',
                'description' => 'متابعة الفواتير والتحصيلات ومبالغ التأمين.',
                'endpoint' => '/pharmacy/sales',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'invoice_number', 'label' => 'رقم الفاتورة'],
                    ['key' => 'customer.name', 'label' => 'العميل'],
                    ['key' => 'grand_total', 'label' => 'الإجمالي', 'format' => 'money'],
                    ['key' => 'paid_amount', 'label' => 'المدفوع', 'format' => 'money'],
                    ['key' => 'insurance_due', 'label' => 'التأمين', 'format' => 'money'],
                    ['key' => 'created_at', 'label' => 'التاريخ', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'purchases' => [
                'title' => 'المشتريات',
                'description' => 'متابعة فواتير التوريد وحالة السداد.',
                'endpoint' => '/pharmacy/purchases',
                'roles' => ['admin', 'storekeeper', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'purchase_number', 'label' => 'رقم الشراء'],
                    ['key' => 'supplier.name', 'label' => 'المورد'],
                    ['key' => 'grand_total', 'label' => 'الإجمالي', 'format' => 'money'],
                    ['key' => 'remaining_amount', 'label' => 'المتبقي', 'format' => 'money'],
                    ['key' => 'payment_status', 'label' => 'السداد', 'format' => 'status'],
                    ['key' => 'purchase_date', 'label' => 'التاريخ', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'expenses' => [
                'title' => 'المصروفات',
                'description' => 'متابعة المصروفات المسجلة حسب نوعها وخزينتها.',
                'endpoint' => '/pharmacy/expenses',
                'roles' => ['admin', 'accountant', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'expense_type.type_name', 'label' => 'نوع المصروف'],
                    ['key' => 'amount', 'label' => 'المبلغ', 'format' => 'money'],
                    ['key' => 'treasury.name', 'label' => 'الخزينة'],
                    ['key' => 'expense_date', 'label' => 'التاريخ', 'format' => 'date'],
                    ['key' => 'user.name', 'label' => 'المسجل'],
                ],
                'fields' => [],
            ],
            'batches' => [
                'title' => 'دفعات المخزون',
                'description' => 'متابعة أرصدة الدفعات وتواريخ الصلاحية.',
                'endpoint' => '/pharmacy/batches',
                'roles' => ['admin', 'pharmacist', 'storekeeper'],
                'writable' => false,
                'columns' => [
                    ['key' => 'medicine.trade_name', 'label' => 'الصنف'],
                    ['key' => 'batch_number', 'label' => 'رقم الدفعة'],
                    ['key' => 'remaining_quantity_base', 'label' => 'المتبقي'],
                    ['key' => 'expiry_date', 'label' => 'الصلاحية', 'format' => 'date'],
                    ['key' => 'branch.name', 'label' => 'الفرع'],
                ],
                'fields' => [],
            ],
            'insurance-claims' => [
                'title' => 'مطالبات التأمين',
                'description' => 'مراجعة المطالبات التأمينية وحالات الاعتماد والتحصيل.',
                'endpoint' => '/pharmacy/insurance-claims',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'claim_number', 'label' => 'رقم المطالبة'],
                    ['key' => 'patient.name', 'label' => 'المريض'],
                    ['key' => 'insurer.name', 'label' => 'شركة التأمين'],
                    ['key' => 'claimed_amount', 'label' => 'المطالب به', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
                    ['key' => 'submitted_at', 'label' => 'تاريخ التقديم', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'journal-entries' => [
                'title' => 'القيود المحاسبية',
                'description' => 'مراجعة القيود الآلية واليدوية وحالة التوازن.',
                'endpoint' => '/pharmacy/journal-entries',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'entry_number', 'label' => 'رقم القيد'],
                    ['key' => 'description', 'label' => 'البيان'],
                    ['key' => 'entry_date', 'label' => 'التاريخ', 'format' => 'date'],
                    ['key' => 'total_debit', 'label' => 'مدين', 'format' => 'money'],
                    ['key' => 'total_credit', 'label' => 'دائن', 'format' => 'money'],
                ],
                'fields' => [],
            ],
            'financial-transactions' => [
                'title' => 'سجل الحركات',
                'description' => 'سجل موحّد للمعاملات المالية المرتبطة بالفواتير والقيود.',
                'endpoint' => '/pharmacy/financial-transactions',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'id', 'label' => 'رقم الحركة'],
                    ['key' => 'created_at', 'label' => 'التاريخ', 'format' => 'date'],
                    ['key' => 'journal_entry.entry_number', 'label' => 'القيد'],
                ],
                'fields' => [],
            ],
            'sale-returns' => [
                'title' => 'مرتجعات المبيعات',
                'description' => 'مراجعة المبالغ المرتجعة وحركة المرتجعات من نقطة البيع.',
                'endpoint' => '/pharmacy/sale-returns',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'return_number', 'label' => 'رقم المرتجع'],
                    ['key' => 'sale.invoice_number', 'label' => 'الفاتورة الأصلية'],
                    ['key' => 'patient_refund_amount', 'label' => 'مبلغ العميل', 'format' => 'money'],
                    ['key' => 'insurance_refund_amount', 'label' => 'مبلغ التأمين', 'format' => 'money'],
                    ['key' => 'reason', 'label' => 'السبب'],
                    ['key' => 'created_at', 'label' => 'التاريخ', 'format' => 'date'],
                ],
                'fields' => [],
            ],
            'purchase-returns' => [
                'title' => 'مرتجعات الموردين',
                'description' => 'متابعة الأصناف المرتجعة إلى الموردين وقيمتها.',
                'endpoint' => '/pharmacy/purchase-returns',
                'roles' => ['admin', 'storekeeper', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'return_number', 'label' => 'رقم المرتجع'],
                    ['key' => 'supplier.name', 'label' => 'المورد'],
                    ['key' => 'total_return_amount', 'label' => 'قيمة المرتجع', 'format' => 'money'],
                    ['key' => 'return_date', 'label' => 'التاريخ', 'format' => 'date'],
                    ['key' => 'reason', 'label' => 'السبب'],
                ],
                'fields' => [],
            ],
            'pos-sessions' => [
                'title' => 'جلسات الكاشير',
                'description' => 'مراجعة الورديات المفتوحة والمغلقة وفروقات الصندوق.',
                'endpoint' => '/pharmacy/pos-sessions',
                'roles' => ['admin', 'pharmacist', 'cashier'],
                'writable' => false,
                'columns' => [
                    ['key' => 'user.name', 'label' => 'الكاشير'],
                    ['key' => 'treasury.name', 'label' => 'الخزينة'],
                    ['key' => 'opened_at', 'label' => 'وقت الفتح', 'format' => 'date'],
                    ['key' => 'expected_cash', 'label' => 'النقد المتوقع', 'format' => 'money'],
                    ['key' => 'difference', 'label' => 'الفرق', 'format' => 'money'],
                    ['key' => 'status', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [],
            ],
            'insurance-plans' => [
                'title' => 'خطط التأمين',
                'description' => 'عرض خطط التغطية ونسب التحمل وحدود المطالبات.',
                'endpoint' => '/pharmacy/insurance-plans',
                'roles' => ['admin', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'name', 'label' => 'اسم الخطة'],
                    ['key' => 'code', 'label' => 'رمز الخطة'],
                    ['key' => 'insurer.name', 'label' => 'شركة التأمين'],
                    ['key' => 'coverage_percentage', 'label' => 'التغطية %'],
                    ['key' => 'patient_copay_percentage', 'label' => 'مساهمة المريض %'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'insurer_contact_id', 'label' => 'شركة التأمين', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/contacts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'name', 'label' => 'اسم الخطة', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'رمز الخطة', 'type' => 'text', 'required' => true],
                    ['name' => 'coverage_percentage', 'label' => 'نسبة التغطية %', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
                    ['name' => 'patient_copay_percentage', 'label' => 'مساهمة المريض %', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
                    ['name' => 'max_coverage_per_claim', 'label' => 'الحد الأقصى للمطالبة', 'type' => 'number', 'min' => 0],
                    ['name' => 'is_active', 'label' => 'نشطة', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'insurance-policies' => [
                'title' => 'وثائق التأمين',
                'description' => 'إدارة الوثائق الطبية وأرقام العضوية وفترات التغطية.',
                'endpoint' => '/pharmacy/insurance-policies',
                'roles' => ['admin', 'pharmacist', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'policy_number', 'label' => 'رقم الوثيقة'],
                    ['key' => 'patient.name', 'label' => 'المريض'],
                    ['key' => 'plan.name', 'label' => 'الخطة'],
                    ['key' => 'valid_from', 'label' => 'بداية التغطية', 'format' => 'date'],
                    ['key' => 'valid_until', 'label' => 'نهاية التغطية', 'format' => 'date'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'patient_contact_id', 'label' => 'المريض', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/contacts?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'insurance_plan_id', 'label' => 'خطة التأمين', 'type' => 'select', 'required' => true, 'options_endpoint' => '/pharmacy/insurance-plans?per_page=100', 'option_value' => 'id', 'option_label' => 'name'],
                    ['name' => 'policy_number', 'label' => 'رقم الوثيقة', 'type' => 'text', 'required' => true],
                    ['name' => 'member_number', 'label' => 'رقم العضوية', 'type' => 'text'],
                    ['name' => 'valid_from', 'label' => 'سارية من', 'type' => 'date', 'required' => true],
                    ['name' => 'valid_until', 'label' => 'سارية حتى', 'type' => 'date'],
                    ['name' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'نشطة', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'expense-types' => [
                'title' => 'أنواع المصروفات',
                'description' => 'تصنيف المصروفات وربطها بحساب محاسبي مناسب.',
                'endpoint' => '/pharmacy/expense-types',
                'roles' => ['admin', 'accountant'],
                'writable' => true,
                'columns' => [
                    ['key' => 'type_name', 'label' => 'نوع المصروف'],
                    ['key' => 'expense_category', 'label' => 'التصنيف'],
                    ['key' => 'account.code', 'label' => 'رمز الحساب'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [
                    ['name' => 'type_name', 'label' => 'نوع المصروف', 'type' => 'text', 'required' => true],
                    ['name' => 'account_id', 'label' => 'رقم حساب المصروف', 'type' => 'number', 'required' => true, 'min' => 1],
                    ['name' => 'expense_category', 'label' => 'التصنيف', 'type' => 'select', 'options' => ['operational' => 'تشغيلي', 'administrative' => 'إداري', 'marketing' => 'تسويقي', 'maintenance' => 'صيانة']],
                    ['name' => 'is_active', 'label' => 'نشط', 'type' => 'checkbox', 'default' => true],
                ],
            ],
            'salary-slips' => [
                'title' => 'مسيرات الرواتب',
                'description' => 'مراجعة الاستحقاقات والمدفوعات وأرصدة الرواتب.',
                'endpoint' => '/pharmacy/salary-slips',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'employee.contact.name', 'label' => 'الموظف'],
                    ['key' => 'year_month_key', 'label' => 'الفترة'],
                    ['key' => 'gross_deserved_amount', 'label' => 'المستحق', 'format' => 'money'],
                    ['key' => 'actual_paid_amount', 'label' => 'المدفوع', 'format' => 'money'],
                    ['key' => 'balance_remaining', 'label' => 'المتبقي', 'format' => 'money'],
                    ['key' => 'payout_status', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [],
            ],
            'employee-work-logs' => [
                'title' => 'سجلات الدوام',
                'description' => 'متابعة ساعات العمل والورديات والساعات الإضافية.',
                'endpoint' => '/pharmacy/employee-work-logs',
                'roles' => ['admin', 'accountant'],
                'writable' => false,
                'columns' => [
                    ['key' => 'employee.contact.name', 'label' => 'الموظف'],
                    ['key' => 'work_date', 'label' => 'التاريخ', 'format' => 'date'],
                    ['key' => 'worked_shifts', 'label' => 'الورديات'],
                    ['key' => 'hours_worked', 'label' => 'ساعات العمل'],
                    ['key' => 'overtime_hours', 'label' => 'ساعات إضافية'],
                    ['key' => 'drawer_discrepancy', 'label' => 'فرق الصندوق', 'format' => 'money'],
                ],
                'fields' => [],
            ],
            'employees' => [
                'title' => 'الموظفون',
                'description' => 'إدارة سجلات الموظفين المرتبطة بجهات الاتصال.',
                'endpoint' => '/pharmacy/employees',
                'roles' => ['admin'],
                'writable' => false,
                'columns' => [
                    ['key' => 'contact.name', 'label' => 'اسم الموظف'],
                    ['key' => 'branch.name', 'label' => 'الفرع'],
                    ['key' => 'job_title', 'label' => 'المسمى الوظيفي'],
                    ['key' => 'is_active', 'label' => 'الحالة', 'format' => 'status'],
                ],
                'fields' => [],
            ],
        ];
    }
}
