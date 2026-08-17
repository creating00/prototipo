<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(Role::class)) {
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            
            if (class_exists(Permission::class)) {
                $adminRole->syncPermissions(Permission::all());
            }

            // Asignar rol admin al usuario jasalle@creatingsoft.net
            $user = User::where('email', 'jasalle@creatingsoft.net')->first();
            if ($user && !$user->hasRole('admin')) {
                $user->assignRole($adminRole);
            }
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
        //
    }
};
