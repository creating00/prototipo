<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition()
    {
        return [
            'name'    => $this->faker->unique()->city(),   // Córdoba, Mendoza, Rosario, etc.
            'address' => $this->faker->optional()->address(),
        ];
    }
}
