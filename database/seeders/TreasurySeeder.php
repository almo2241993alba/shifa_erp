<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Treasury;
use Illuminate\Database\Seeder;

class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        $cashAccount = Account::where('code', '1101')->firstOrFail();
        $bankAccount = Account::where('code', '1102')->firstOrFail();

        Branch::query()->each(function (Branch $branch) use ($cashAccount, $bankAccount): void {
            Treasury::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => 'درج الكاشير الرئيسي'],
                ['account_id' => $cashAccount->id, 'type' => 'cash_drawer', 'current_balance' => 0]
            );

            Treasury::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => 'الخزنة الرئيسية'],
                ['account_id' => $cashAccount->id, 'type' => 'main_safe', 'current_balance' => 0]
            );

            Treasury::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => 'الحساب البنكي / الشبكة'],
                ['account_id' => $bankAccount->id, 'type' => 'bank', 'current_balance' => 0]
            );
        });
    }
}
