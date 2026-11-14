<?php

namespace Database\Seeders;

use App\Models\DosageForm;
use Illuminate\Database\Seeder;

class DosageFormSeeder extends Seeder
{
    public function run(): void
    {
        $forms = [
            'حبوب (Tablets)',
            'كبسولات (Capsules)',
            'شراب (Syrup)',
            'حقن / أمبولات (Injections)',
            'لبوس / فتيل (Suppositories)',
            'قطرات (Drops)',
            'مرهم / كريم (Ointment / Cream)',
            'مستلزمات طبية (Medical Supplies)',
        ];

        foreach ($forms as $name) {
            DosageForm::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
