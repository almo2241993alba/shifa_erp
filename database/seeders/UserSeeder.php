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
        $password = config('app.initial_admin_password');

        if (!$adminExists && (!is_string($password) || mb_strlen($password) < 12)) {
            throw new RuntimeException('Set ADMIN_INITIAL_PASSWORD to a value of at least 12 characters before seeding.');
        }

        $branch = Branch::firstOrFail();

        $contact = Contact::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'المدير العام'],
            [
                'is_employee' => false,
                'is_active' => true,
            ]
        );

        if (!$adminExists) {
            User::create([
                'branch_id' => $branch->id,
                'contact_id' => $contact->id,
                'name' => 'المدير العام',
                'username' => 'admin',
                'email' => 'admin@shifa.com',
                'password' => $password,
                'role' => 'admin',
                'is_active' => true,
            ]);
        }
    }
}
