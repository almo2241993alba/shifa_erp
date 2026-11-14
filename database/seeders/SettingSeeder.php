<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $salesAccount = Account::where('code', '4101')->firstOrFail();
        $inventoryAccount = Account::where('code', '1201')->firstOrFail();
        $cogsAccount = Account::where('code', '5101')->firstOrFail();
        $vatPayableAccount = Account::where('code', '2201')->firstOrFail();
        $vatReceivableAccount = Account::where('code', '1401')->firstOrFail();

        Setting::firstOrCreate(
            ['pharmacy_name' => 'صيدلية الشفاء'],
            [
                'tax_rate' => 15.00,
                'currency_code' => 'SAR',
                'min_expiry_alert_days' => 90,
                'sales_account_id' => $salesAccount->id,
                'inventory_account_id' => $inventoryAccount->id,
                'cogs_account_id' => $cogsAccount->id,
                'vat_payable_account_id' => $vatPayableAccount->id,
                'vat_receivable_account_id' => $vatReceivableAccount->id,
            ]
        );
    }
}
