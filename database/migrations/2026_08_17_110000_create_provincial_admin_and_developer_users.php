<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Province;
use App\Enums\RoleLabel;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asegurar la existencia de los roles principales
        if (class_exists(Role::class)) {
            $adminRole = Role::firstOrCreate(['name' => RoleLabel::ADMIN->value, 'guard_name' => 'web']);
            $provincialRole = Role::firstOrCreate(['name' => RoleLabel::PROVINCIAL_ADMIN->value, 'guard_name' => 'web']);
        }

        // Buscar la provincia de Córdoba
        $cordobaProvince = Province::where('name', 'Córdoba')
            ->orWhere('name', 'Cordoba')
            ->orWhere('name', 'LIKE', '%Cordoba%')
            ->orWhere('name', 'LIKE', '%Córdoba%')
            ->first();

        // 1. Crear / Actualizar usuario Administrador Provincial (Daniel Tecnonauta)
        $provincialUser = User::firstOrCreate(
            ['email' => 'administradorsedecordoba@nauta.com'],
            [
                'name' => 'Daniel Tecnonauta',
                'password' => Hash::make('123456789'),
                'province_id' => $cordobaProvince?->id,
            ]
        );

        if ($provincialUser && !$provincialUser->hasRole(RoleLabel::PROVINCIAL_ADMIN->value)) {
            $provincialUser->assignRole(RoleLabel::PROVINCIAL_ADMIN->value);
        }

        if ($cordobaProvince && !$provincialUser->province_id) {
            $provincialUser->update(['province_id' => $cordobaProvince->id]);
        }

        // 2. Crear / Actualizar usuario Desarrollador / Soporte Técnico
        $developerUser = User::firstOrCreate(
            ['email' => 'soporte@creatingsoft.net'],
            [
                'name' => 'Soporte CreatingSoft',
                'password' => Hash::make('soporte123'),
            ]
        );

        if ($developerUser) {
            // Se le asignan ambos roles para otorgarle todos los permisos globales y provinciales
            $developerUser->syncRoles([RoleLabel::ADMIN->value, RoleLabel::PROVINCIAL_ADMIN->value]);
        }

        // Limpiar la caché de permisos de Spatie
        if (app()->has('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::whereIn('email', [
            'administradorsedecordoba@nauta.com',
            'soporte@creatingsoft.net'
        ])->delete();

        if (app()->has('cache')) {
            app('cache')->forget(config('permission.cache.key', 'spatie.permission.cache'));
        }
    }
};
