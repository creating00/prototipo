<?php
// config/admin-side-menu.php

return [
    'items' => [
        [
            'type' => 'submenu',
            'icon' => 'bi bi-speedometer',
            'label' => 'Dashboard',
            'menuOpen' => true,
            'active' => true,
            'subitems' => [
                ['href' => '#', 'label' => 'Dashboard v1'],
                ['href' => '#', 'label' => 'Dashboard v2'],
                ['href' => '#', 'label' => 'Dashboard v3', 'active' => true],
            ],
        ],
        [
            'type' => 'single',
            'icon' => 'bi bi-palette',
            'label' => 'Theme Generate',
            'href' => '#',
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-clipboard-fill',
            'label' => 'Layout Options',
            'badge' => '6',
            'subitems' => [
                ['href' => '#', 'label' => 'Default Sidebar'],
                ['href' => '#', 'label' => 'Fixed Sidebar'],
                ['href' => '#', 'label' => 'Fixed Header'],
                ['href' => '#', 'label' => 'Fixed Footer'],
                ['href' => '#', 'label' => 'Fixed Complete'],
            ],
        ],
        [
            'type' => 'nested',
            'icon' => 'bi bi-box-arrow-in-right',
            'label' => 'Auth',
            'children' => [
                [
                    'type' => 'nested',
                    'icon' => 'bi bi-box-arrow-in-right',
                    'label' => 'Version 1',
                    'children' => [
                        ['href' => '#', 'label' => 'Login'],
                        ['href' => '#', 'label' => 'Register'],
                    ],
                ],
                [
                    'type' => 'nested',
                    'icon' => 'bi bi-box-arrow-in-right',
                    'label' => 'Version 2',
                    'children' => [
                        ['href' => '#', 'label' => 'Login'],
                        ['href' => '#', 'label' => 'Register'],
                    ],
                ],
                [
                    'href' => '#',
                    'label' => 'Lockscreen',
                ],
            ],
        ],
        [
            'type' => 'header',
            'title' => 'EXAMPLES',
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
            'icon' => 'bi bi-people-fill',
            'label' => 'Usuarios',
            'subitems' => [
                ['href' => '#', 'label' => 'Listar Usuarios'],
                ['href' => '#', 'label' => 'Crear Usuario'],
                ['href' => '#', 'label' => 'Roles'],
            ],
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-cart-fill',
            'label' => 'Ventas',
            'subitems' => [
                ['href' => '#', 'label' => 'Nueva Venta'],
                ['href' => '#', 'label' => 'Historial'],
                ['href' => '#', 'label' => 'Reportes'],
            ],
        ],
    ],
];
