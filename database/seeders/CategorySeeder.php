<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Módulos', 'is_system' => true],
            ['name' => 'Baterías', 'is_system' => true],
            ['name' => 'Pines de Carga', 'is_system' => true],
            ['name' => 'Glass', 'is_system' => true],
            ['name' => 'Repuestos Varios', 'is_system' => true],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
