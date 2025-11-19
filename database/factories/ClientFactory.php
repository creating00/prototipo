<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'document'   => $this->faker->unique()->numerify('########'), // 8 dígitos
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
        ];
    }
}
