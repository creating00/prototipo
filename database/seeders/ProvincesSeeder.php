<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Province;

class ProvincesSeeder extends Seeder
{
    public function run(): void
    {
        $url = 'https://apis.datos.gob.ar/georef/api/provincias';

        $response = Http::get($url);

        if (! $response->successful()) {
            $this->command->error('Error fetching provinces API');
            return;
        }

        $provinces = $response->json('provincias');

        foreach ($provinces as $prov) {
            Province::updateOrCreate(
                ['api_id' => $prov['id']],
                [
                    'name' => $prov['nombre'],
                    'name_long' => $prov['nombre_largo'] ?? $prov['nombre'],
                ]
            );
        }

        $this->command->info('Provinces loaded successfully.');
    }
}
