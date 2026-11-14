<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // 1. الأصول
        $assets = Account::firstOrCreate(['code' => '1000'], ['name' => 'الأصول', 'account_type' => 'asset', 'is_transactional' => false]);
        Account::firstOrCreate(['code' => '1101'], ['parent_id' => $assets->id, 'name' => 'الصندوق ودرج الكاشير', 'account_type' => 'asset']);
        Account::firstOrCreate(['code' => '1102'], ['parent_id' => $assets->id, 'name' => 'البنك وأجهزة الشبكة', 'account_type' => 'asset']);
        Account::firstOrCreate(['code' => '1201'], ['parent_id' => $assets->id, 'name' => 'مخزون الأدوية والمستلزمات', 'account_type' => 'asset']);
        Account::firstOrCreate(['code' => '1301'], ['parent_id' => $assets->id, 'name' => 'ذمم العملاء (الآجل)', 'account_type' => 'asset']);
        Account::firstOrCreate(['code' => '1401'], ['parent_id' => $assets->id, 'name' => 'ضريبة القيمة المضافة - مدخلات (مشتريات)', 'account_type' => 'asset']);

        // 2. الالتزامات
        $liabilities = Account::firstOrCreate(['code' => '2000'], ['name' => 'الالتزامات والخصوم', 'account_type' => 'liability', 'is_transactional' => false]);
        Account::firstOrCreate(['code' => '2101'], ['parent_id' => $liabilities->id, 'name' => 'الموردون وشركات الأدوية', 'account_type' => 'liability']);
        Account::firstOrCreate(['code' => '2201'], ['parent_id' => $liabilities->id, 'name' => 'ضريبة القيمة المضافة - مخرجات (مبيعات)', 'account_type' => 'liability']);
        Account::firstOrCreate(['code' => '2301'], ['parent_id' => $liabilities->id, 'name' => 'مستحقات الرواتب', 'account_type' => 'liability']);

        // 3. الإيرادات
        $revenues = Account::firstOrCreate(['code' => '4000'], ['name' => 'الإيرادات', 'account_type' => 'revenue', 'is_transactional' => false]);
        Account::firstOrCreate(['code' => '4101'], ['parent_id' => $revenues->id, 'name' => 'إيرادات مبيعات الصيدلية', 'account_type' => 'revenue']);

        // 4. المصروفات والتكاليف
        $expenses = Account::firstOrCreate(['code' => '5000'], ['name' => 'المصروفات والتكاليف', 'account_type' => 'expense', 'is_transactional' => false]);
        Account::firstOrCreate(['code' => '5101'], ['parent_id' => $expenses->id, 'name' => 'تكلفة البضاعة المباعة (COGS)', 'account_type' => 'expense']);
        Account::firstOrCreate(['code' => '5201'], ['parent_id' => $expenses->id, 'name' => 'الرواتب والأجور', 'account_type' => 'expense']);
        Account::firstOrCreate(['code' => '5301'], ['parent_id' => $expenses->id, 'name' => 'المصروفات التشغيلية والعمومية', 'account_type' => 'expense']);
    }
}
