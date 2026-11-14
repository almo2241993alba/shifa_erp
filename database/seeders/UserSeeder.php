<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminExists = User::where('email', 'admin@shifa.com')->exists();
        $cashierExists = User::where('email', 'cashier@shifa.com')->exists();
        $adminPassword = config('app.initial_admin_password');
        $cashierPassword = config('app.initial_cashier_password');

        if (! $adminExists && (! is_string($adminPassword) || mb_strlen($adminPassword) < 12)) {
            throw new RuntimeException('Set ADMIN_INITIAL_PASSWORD to a value of at least 12 characters before seeding.');
        }

        if (! $cashierExists && (! is_string($cashierPassword) || mb_strlen($cashierPassword) < 12)) {
            throw new RuntimeException('Set CASHIER_INITIAL_PASSWORD to a value of at least 12 characters before seeding.');
        }

        $branch = Branch::firstOrFail();

        $contact = Contact::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'المدير العام'],
            [
                'is_employee' => false,
                'is_active' => true,
            ]
        );

        if (! $adminExists) {
            User::create([
                'branch_id' => $branch->id,
                'contact_id' => $contact->id,
                'name' => 'المدير العام',
                'username' => 'admin',
                'email' => 'admin@shifa.com',
                'password' => $adminPassword,
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        if (! $cashierExists) {
            User::create([
                'branch_id' => $branch->id,
                'name' => 'كاشير الصيدلية',
                'username' => 'cashier',
                'email' => 'cashier@shifa.com',
                'password' => $cashierPassword,
                'role' => 'cashier',
                'is_active' => true,
            ]);
        }
    }
}
