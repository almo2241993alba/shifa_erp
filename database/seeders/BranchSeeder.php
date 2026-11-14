<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'BR-001'],
            [
                'name' => 'الفرع الرئيسي - صيدلية الشفاء',
                'is_main' => true,
                'is_active' => true,
            ]
        );
    }
}
