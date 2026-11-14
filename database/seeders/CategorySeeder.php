<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'مسكنات وخافضات حرارة',
            'مضادات حيوية',
            'أدوية الملاريا والطفيليات',
            'أدوية الجهاز الهضمي',
            'أدوية الضغط والقلب',
            'أدوية السكري',
            'فيتامينات ومكملات غذائية',
            'مستلزمات طبية وحقن',
            'مستحضرات تجميل وعناية',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
