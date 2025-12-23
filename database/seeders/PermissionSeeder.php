<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    // Estructura centralizada de permisos
    private array $permissionStructure = [
        'users' => ['view', 'create', 'update', 'delete', 'assign_roles', 'reset_password'],
        'branches' => ['view', 'create', 'update', 'delete'],
        'categories' => ['view', 'create', 'update', 'delete'],
        'products' => ['view', 'create', 'update', 'delete', 'import', 'export'],
        'clients' => ['view', 'create', 'update', 'delete'],
        'providers' => ['view', 'create', 'update', 'delete'],
        'orders' => ['view', 'create', 'update', 'cancel', 'approve', 'print'],
        'sales' => ['view', 'create', 'update', 'cancel', 'print', 'refund'],
        'payments' => ['view', 'create', 'cancel', 'refund'],
        'expenses' => ['view', 'create', 'update', 'delete', 'approve'],
        'expense_types' => ['view', 'create', 'update', 'delete'],
        'ratings' => ['view', 'create', 'moderate', 'delete'],
        'reports' => ['view_sales', 'view_expenses', 'view_inventory', 'export'],
        'settings' => ['view', 'update'],
    ];

    // Roles con permisos definidos (puede moverse a config/roles.php)
    private array $roles = [
        'admin' => [],
        'editor' => [
            'products.view',
            'products.create',
            'products.update',
            'categories.view',
            'categories.create',
            'categories.update',
        ],
        'seller' => [
            'products.view',
            'clients.view',
            'clients.create',
            'sales.view',
            'sales.create',
            'sales.print',
            'payments.create',
        ]
    ];

    public function run(): void
    {
        // Crear todos los permisos
        $this->createPermissions();

        // Crear roles y asignar permisos
        $this->createRoles();
    }

    /**
     * Genera todos los permisos basados en la estructura
     */
    private function createPermissions(): void
    {
        foreach ($this->permissionStructure as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$resource}.{$action}",
                    'guard_name' => 'web', // Especificar guard si es necesario
                ]);
            }
        }
    }

    /**
     * Crea roles y asigna permisos
     */
    private function createRoles(): void
    {
        foreach ($this->roles as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Método útil para obtener todos los permisos generados
     * (útil para UI/selectores)
     */
    public static function getAllPermissions(): array
    {
        $instance = new self;
        $permissions = [];

        foreach ($instance->permissionStructure as $resource => $actions) {
            foreach ($actions as $action) {
                $permissions[] = "{$resource}.{$action}";
            }
        }

        return $permissions;
    }
}
