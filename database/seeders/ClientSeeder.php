<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::updateOrCreate(
            ['document' => config('app.default_client_document')],
            [
                'full_name' => config('app.default_client_name'),
                'phone'     => '00000000',
                'address'   => 'Ciudad',
                'email'     => null,
                'is_system' => true, // Protegido para que no lo borren
            ]
        );
    }
}
