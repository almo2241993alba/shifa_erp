<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'كرتون (Carton)',
            'علبة (Box)',
            'شريط (Strip)',
            'زجاجة (Bottle)',
            'أمبول / حقنة (Ampoule)',
            'أنبوب / مرهم (Tube)',
            'قطعة / سرنجة (Piece)',
        ];

        foreach ($units as $name) {
            Unit::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
