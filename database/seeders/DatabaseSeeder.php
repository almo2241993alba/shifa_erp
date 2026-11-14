<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            AccountSeeder::class,
            SettingSeeder::class,
            ContactSeeder::class,
            UserSeeder::class,
            TreasurySeeder::class,
            CategorySeeder::class,
            DosageFormSeeder::class,
            UnitSeeder::class,
            ExpenseTypeSeeder::class,
        ]);
    }
}
