<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // Obtenemos todas las sucursales existentes
        $branches = Branch::all();

        foreach ($branches as $branch) {
            Client::updateOrCreate(
                [
                    'document'  => config('app.default_client_document'),
                    'branch_id' => $branch->id, // Identificador único por sucursal
                ],
                [
                    'full_name' => config('app.default_client_name'),
                    'phone'     => '00000000',
                    'address'   => 'Ciudad',
                    'email'     => null,
                    'is_system' => true,
                ]
            );
        }
    }
}
