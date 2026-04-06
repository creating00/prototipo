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
        'orders' => ['view', 'view_own', 'view_money', 'create_client', 'create_branch', 'update', 'approve', 'cancel', 'print'],
        'order_items' => ['view', 'create', 'update', 'delete'],

        // Órdenes a proveedores
        'provider_orders' => ['view', 'create', 'approve', 'cancel', 'print'],
        'provider_order_items' => ['view', 'create', 'update', 'delete'],

        // Ventas
        'sales' => ['view', 'create_client', 'create_branch', 'update', 'delete', 'cancel', 'print', 'refund', 'view_money'],
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

        //Bancos
        'banks' => ['view', 'create', 'update', 'delete'],

        //Cuentas Bancarias
        'bank_accounts' => ['view', 'create', 'update', 'delete'],

        // Promociones
        'promotions' => ['view', 'create', 'update', 'delete'],
        'promotion_images' => ['view', 'create', 'update', 'delete'],
    ];

    // Roles con permisos definidos (puede moverse a config/roles.php)
    private array $roles = [
        'seller' => [
            'orders.view',
            'products.view',
            'clients.view',
            'clients.create',
            'sales.view',
            'sales.create_client',
            'sales.create_branch',
            'sales.print',
            'payments.create',
            'expenses.create'
        ]
    ];

    /**
     * Obtiene todos los permisos excepto los recursos o acciones específicas indicadas.
     * * @param array $excludeResources Lista de recursos completos a excluir (ej: ['settings', 'analytics'])
     * @param array $excludeActions Lista de permisos específicos (ej: ['sales.delete', 'users.reset_password'])
     */
    private function getPermissionsExcept(array $excludeResources = [], array $excludeActions = []): array
    {
        $filteredPermissions = [];

        foreach ($this->permissionStructure as $resource => $actions) {
            // Si el recurso completo está en la lista de exclusión, lo saltamos
            if (in_array($resource, $excludeResources)) {
                continue;
            }

            foreach ($actions as $action) {
                $permissionName = "{$resource}.{$action}";

                // Si la combinación recurso.acción está en la lista de exclusión, la saltamos
                if (in_array($permissionName, $excludeActions)) {
                    continue;
                }

                $filteredPermissions[] = $permissionName;
            }
        }

        return $filteredPermissions;
    }

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
        // Crear el Admin (Siempre activo)
        $this->setupAdminRole();

        // Crear el Manager (Comentado por petición del cliente)
        // $this->setupManagerRole();

        // Crear el Seller (Siempre activo)
        $this->setupSellerRole();
    }

    /**
     * Configura el rol de Administrador con acceso total.
     */
    private function setupAdminRole(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());
    }

    /**
     * Configura el rol de Manager con exclusiones específicas.
     * (Mantener como referencia o activar si el cliente lo solicita)
     */
    private function setupManagerRole(): void
    {
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $excludeResources = [
            'settings',
            'analytics',
            'price_modifications'
        ];

        $excludeActions = [
            'sales.update',
            'sales.delete',
            'sales.refund',
            'users.delete',
            'expenses.delete'
        ];

        $permissions = $this->getPermissionsExcept($excludeResources, $excludeActions);
        $manager->syncPermissions($permissions);
    }

    /**
     * Configura el rol de Vendedor basado en el array manual.
     */
    private function setupSellerRole(): void
    {
        $seller = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);

        if (!empty($this->roles['seller'])) {
            $seller->syncPermissions($this->roles['seller']);
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
