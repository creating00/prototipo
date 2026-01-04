<?php

return [
    'cards' => [
        'analytics' => [
            'title' => 'Analitico',
            'value' => '.',
            'color' => 'custom',
            'customBgColor' => "#6db2ebff",
            'icon' => 'analytics',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.analytics.index', 'label' => 'Ver Descuentos'],
            'description' => 'Ver Datos'
        ],
        'discounts' => [
            'title' => 'Descuentos',
            'value' => 0,
            'color' => 'custom',
            'customBgColor' => "#eb6d6dff",
            'icon' => 'discount',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.discounts.index', 'label' => 'Ver Descuentos'],
            'description' => 'Gestion de Descuentos'
        ],
        'sales' => [
            'title' => 'Ventas Totales',
            'value' => 0,
            'color' => 'success',
            'icon' => 'cart-check',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.sales.index', 'label' => 'Ver Ventas'],
            'description' => 'Ventas realizadas hoy'
        ],
        'orders' => [
            'title' => 'Pedidos',
            'value' => 0,
            'color' => 'primary',
            'icon' => 'cart',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.orders.index', 'label' => 'Gestionar Pedidos'],
            'description' => 'Pedidos pendientes'
        ],
        'audits' => [
            'title' => 'Auditoría de Precios',
            'value' => 0,
            'color' => 'custom',
            'customBgColor' => "#3b82f6ff",
            'icon' => 'search',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.price-modifications.index', 'label' => 'Ver Auditoría'],
            'description' => 'Historial de modificaciones de precios'
        ],
        'provider-orders' => [
            'title' => 'Pedidos a Proveedor',
            'value' => 0,
            'color' => 'custom',
            'customBgColor' => "#6deb8dff",
            'icon' => 'truck-check',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.provider-orders.index', 'label' => 'Gestionar Pedidos'],
            'description' => 'Pedidos pendientes'
        ],
        'products' => [
            'title' => 'Productos',
            'value' => 0,
            'color' => 'info',
            'icon' => 'box',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.products.index', 'label' => 'Listar Productos'],
            'description' => 'Total de productos en catálogo'
        ],
        'clients' => [
            'title' => 'Clientes',
            'value' => 0,
            'color' => 'warning',
            'icon' => 'users',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.clients.index', 'label' => 'Ver Clientes'],
            'description' => 'Clientes registrados'
        ],
        'expenses' => [
            'title' => 'Gastos',
            'value' => '$0',
            'color' => 'danger',
            'icon' => 'trending-down',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.expenses.index', 'label' => 'Control de Gastos'],
            'description' => 'Gastos del mes actual'
        ],
        'providers' => [
            'title' => 'Proveedores',
            'value' => 0,
            'color' => 'secondary',
            'icon' => 'truck',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.providers.index', 'label' => 'Ver Proveedores'],
            'description' => 'Proveedores activos'
        ],
        'branches' => [
            'title' => 'Sucursales',
            'value' => 0,
            'color' => 'custom',
            'customBgColor' => "#6f42c1",
            'icon' => 'geo-alt',
            'viewBox' => '0 0 24 24',
            'route' => ['href' => 'web.branches.index', 'label' => 'Listar Sucursales'],
            'description' => 'Puntos de venta'
        ],
        // 'payments' => [
        //     'title' => 'Pagos Recibidos',
        //     'value' => 0,
        //     'color' => 'custom',
        //     'customBgColor' => "#20c997",
        //     'icon' => 'cash-stack',
        //     'viewBox' => '0 0 24 24',
        //     'route' => ['href' => 'web.payments.index', 'label' => 'Ver Pagos'],
        //     'description' => 'Cobros procesados'
        // ],
        // 'ratings' => [
        //     'title' => 'Valoraciones',
        //     'value' => 0,
        //     'color' => 'custom',
        //     'customBgColor' => "#fd7e14",
        //     'icon' => 'star',
        //     'viewBox' => '0 0 24 24',
        //     'route' => ['href' => 'web.ratings.index', 'label' => 'Ver Reseñas'],
        //     'description' => 'Opiniones de clientes'
        // ],
    ],

    'colors' => [
        'primary'   => 'text-bg-primary',
        'success'   => 'text-bg-success',
        'warning'   => 'text-bg-warning',
        'danger'    => 'text-bg-danger',
        'info'      => 'text-bg-info',
        'secondary' => 'text-bg-secondary',
    ],
];
