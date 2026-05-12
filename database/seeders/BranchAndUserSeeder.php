<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BranchAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Configuración de provincias y sus datos
        $locations = [
            // 'Jujuy' => [
            //     'branch_name' => 'Sucursal Jujuy',
            //     'address' => 'Av. Libertador San Martín 123, San Salvador de Jujuy',
            //     'email' => 'admin@admin.com',
            //     'admin_name' => 'Administrador Jujuy'
            // ],
            // 'Salta' => [
            //     'branch_name' => 'Sucursal Salta',
            //     'address' => 'Av. Belgrano 456, Salta Capital',
            //     'email' => 'admin_salta@admin.com',
            //     'admin_name' => 'Administrador Salta'
            // ],
            'Córdoba' => [
                'branch_name' => 'Sucursal Córdoba',
                'address' => 'Colón 789, Córdoba Capital',
                'email' => 'admin_cordoba@admin.com',
                'admin_name' => 'Administrador Córdoba'
            ],
        ];

        foreach ($locations as $provinceName => $data) {
            $this->createBranchAndAdmin($provinceName, $data);
        }
    }

    /**
     * Procesa la creación de sucursal y usuario por provincia.
     */
    private function createBranchAndAdmin(string $provinceName, array $data): void
    {
        $province = Province::where('name', $provinceName)->first();

        if (!$province) {
            $this->command->error("La provincia {$provinceName} no existe en la base de datos.");
            return;
        }

        // Crear o recuperar sucursal
        $branch = Branch::firstOrCreate(
            ['name' => $data['branch_name']],
            [
                'province_id' => $province->id,
                'address' => $data['address'],
            ]
        );

        // Crear o recuperar usuario
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['admin_name'],
                'password' => Hash::make('12345678'),
                'branch_id' => $branch->id,
            ]
        );

        // Asignar rol si no lo tiene
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        $this->command->info("Sucursal y administrador creados para: {$provinceName}");
    }
}
