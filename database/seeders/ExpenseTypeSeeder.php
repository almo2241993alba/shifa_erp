<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $expenseAccount = Account::where('code', '5301')->firstOrFail();

        $types = [
            ['type_name' => 'أكياس ومواد تغليف', 'expense_category' => 'operational'],
            ['type_name' => 'فواتير كهرباء ومياه وإنترنت', 'expense_category' => 'operational'],
            ['type_name' => 'إيجار مقر الصيدلية', 'expense_category' => 'administrative'],
            ['type_name' => 'صيانة أجهزة التبريد والثلاجات', 'expense_category' => 'maintenance'],
            ['type_name' => 'رسوم التراخيص البلدية والصحية', 'expense_category' => 'administrative'],
            ['type_name' => 'نثريات وضيافة', 'expense_category' => 'operational'],
        ];

        foreach ($types as $type) {
            ExpenseType::firstOrCreate(
                ['type_name' => $type['type_name']],
                [
                    'account_id' => $expenseAccount->id,
                    'expense_category' => $type['expense_category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
