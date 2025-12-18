<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use App\Models\Province;
use Illuminate\Support\Facades\Hash;

class BranchAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar la provincia de Jujuy (según el nombre devuelto por la API)
        // La API de Georef devuelve "Jujuy" exactamente así
        $province = Province::where('name', 'Jujuy')->first();

        if (!$province) {
            $this->command->error('La provincia de Jujuy no existe en la base de datos. Asegúrate de haber ejecutado ProvincesSeeder.');
            return;
        }

        // Crear una sucursal en Jujuy
        $branch = Branch::firstOrCreate(
            ['name' => 'Sucursal Jujuy'],
            [
                'province_id' => $province->id,
                'address' => 'Av. Libertador San Martín 123, San Salvador de Jujuy',
            ]
        );

        // Crear un usuario asociado a esa sucursal
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador Jujuy',
                'password' => Hash::make('12345678'),
                'branch_id' => $branch->id,
            ]
        );

        $this->command->info('Sucursal en Jujuy y usuario administrador creados.');
    }
}
