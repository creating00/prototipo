<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Branch;
use App\Models\Category;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'code' => $this->faker->unique()->bothify('T###'),
            'name' => $this->faker->word(),
            'image' => null,
            'description' => $this->faker->sentence(),
            'stock' => $this->faker->numberBetween(0, 100),
            'branch_id' => Branch::inRandomOrder()->value('id') ?? Branch::factory(),
            'category_id' => Category::inRandomOrder()->value('id') ?? Category::factory(),
            'purchase_price' => $this->faker->randomFloat(2, 1, 50),
            'sale_price' => $this->faker->randomFloat(2, 50, 100),
        ];
    }
}
