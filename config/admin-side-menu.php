<?php
// config/admin-side-menu.php

return [
    'items' => [
        [
            'type' => 'header',
            'title' => 'ADMINISTRACIÓN',
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-list-ol',
            'label' => 'Pedidos',
            'subitems' => [
                ['href' => 'web.orders.index', 'label' => 'Listar Pedidos'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-cart-fill',
            'label' => 'Ventas',
            'subitems' => [
                ['href' => 'web.sales.index', 'label' => 'Listar Ventas'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-box-seam-fill',
            'label' => 'Productos',
            'subitems' => [
                ['href' => 'web.products.index', 'label' => 'Listar Productos'],
                ['href' => 'web.products.create', 'label' => 'Crear Producto'],
                ['href' => 'web.categories.index', 'label' => 'Categorías'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-list-ol',
            'label' => 'Sucursales',
            'subitems' => [
                ['href' => 'web.branches.index', 'label' => 'Listar Sucursales'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-people-fill',
            'label' => 'Usuarios (No definido)',
            'subitems' => [
                ['href' => '#', 'label' => 'Listar Usuarios'],
                ['href' => '#', 'label' => 'Crear Usuario'],
                ['href' => '#', 'label' => 'Roles'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-cart-fill',
            'label' => 'Proveedores',
            'subitems' => [
                ['href' => 'web.providers.index', 'label' => 'Listar Proveedores'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-cart-fill',
            'label' => 'Gastos',
            'subitems' => [
                ['href' => 'web.expenses.index', 'label' => 'Listar Gastos'],
            ],
        ],
        // [
        //     'type' => 'submenu',
        //     'icon' => 'bi bi-cart-fill',
        //     'label' => 'Ventas',
        //     'subitems' => [
        //         ['href' => '#', 'label' => 'Nueva Venta'],
        //         ['href' => '#', 'label' => 'Historial'],
        //         ['href' => '#', 'label' => 'Reportes'],
        //     ],
        // ],
    ],
];
