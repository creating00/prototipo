<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    // Estructura centralizada de permisos
    private array $permissionStructure = [

        // Seguridad / Usuarios
        'users' => ['view', 'create', 'update', 'delete', 'assign_roles', 'reset_password'],

        // Ubicación y sucursales
        'branches' => ['view', 'create', 'update', 'delete'],
        'provinces' => ['view', 'create', 'update', 'delete'],

        // Catálogos
        'categories' => ['view', 'create', 'update', 'delete'],
        'expense_types' => ['view', 'create', 'update', 'delete'],
        'discounts' => ['view', 'create', 'update', 'delete'],

        // Productos
        'products' => ['view', 'create', 'update', 'delete', 'import', 'export'],
        'product_branches' => ['view', 'create', 'update', 'delete'],
        'product_branch_prices' => ['view', 'create', 'update'],
        'product_provider_prices' => ['view', 'create', 'update'],
        'provider_products' => ['view', 'create', 'update', 'delete'],

        // Clientes
        'clients' => ['view', 'create', 'update', 'delete'],
        'client_accounts' => ['view', 'adjust'],

        // Proveedores
        'providers' => ['view', 'create', 'update', 'delete'],

        // Órdenes internas
        'orders' => ['view', 'view_own', 'create_client', 'create_branch', 'update', 'approve', 'cancel', 'print'],
        'order_items' => ['view', 'create', 'update', 'delete'],

        // Órdenes a proveedores
        'provider_orders' => ['view', 'create', 'approve', 'cancel', 'print'],
        'provider_order_items' => ['view', 'create', 'update', 'delete'],

        // Ventas
        'sales' => ['view', 'create_client', 'create_branch', 'update', 'delete', 'cancel', 'print', 'refund'],
        'sale_items' => ['view', 'create', 'update', 'delete'],

        // Pagos
        'payments' => ['view', 'create', 'cancel', 'refund'],

        // Gastos
        'expenses' => ['view', 'create', 'update', 'delete', 'approve'],

        // Ratings
        'ratings' => ['view', 'create', 'moderate', 'delete'],

        // Reportes
        'reports' => ['view_sales', 'view_expenses', 'view_inventory', 'export'],

        // Configuración
        'settings' => ['view', 'update'],

        // Analítica / Dashboards
        'analytics' => ['view'],

        // Auditoría / Históricos
        'price_modifications' => ['view'],

        //Monto por tipo de reraparacion
        'repair_amounts' => ['view', 'create', 'update', 'delete'],
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
            'sales.create_client',
            'sales.create_branch',
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
