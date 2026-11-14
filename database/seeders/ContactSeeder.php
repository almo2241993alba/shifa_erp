<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrFail();
        $customerAccount = Account::where('code', '1301')->firstOrFail();
        $supplierAccount = Account::where('code', '2101')->firstOrFail();

        Contact::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'عميل ومورد نقدي عام'],
            [
                'customer_account_id' => $customerAccount->id,
                'supplier_account_id' => $supplierAccount->id,
                'contact_type' => 'B2C',
                'is_customer' => true,
                'is_supplier' => true,
                'is_employee' => false,
                'credit_limit' => 0.0000,
                'is_active' => true,
            ]
        );
    }
}
