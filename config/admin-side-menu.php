<?php
// config/admin-side-menu.php

return [
    'items' => [
        [
            'type'  => 'header',
            'title' => 'ADMINISTRACIÓN',
        ],
        [
            'type' => 'submenu',
            'icon' => 'bi bi-person-badge',
            'label' => 'Usuarios',
            'subitems' => [
                ['href' => 'web.users.index', 'label' => 'Listar Usuarios'],
                ['href' => 'web.users.create', 'label' => 'Crear Usuario'],
                // ['href' => '#', 'label' => 'Roles'],
            ],
        ],

        // VENTAS
        [
            'type' => 'single',
            'icon' => 'bi bi-cart-fill',
            'label' => 'Ventas',
            'href'  => 'web.sales.create-client',
        ],
        [
            'type' => 'single',
            'icon' => 'bi bi-receipt',
            'label' => 'Registro de Ventas',
            'href'  => 'web.sales.index',
        ],

        // GASTOS
        [
            'type' => 'single',
            'icon' => 'bi bi-cash-coin',
            'label' => 'Gastos',
            'href'  => 'web.expenses.index',
        ],

        // PRODUCTOS
        [
            'type' => 'single',
            'icon' => 'bi bi-box-seam',
            'label' => 'Productos',
            'href'  => 'web.products.index',
        ],

        // CLIENTES
        [
            'type' => 'single',
            'icon' => 'bi bi-people-fill',
            'label' => 'Clientes',
            'href'  => 'web.clients.index',
        ],

        // SUCURSALES
        [
            'type' => 'single',
            'icon' => 'bi bi-building',
            'label' => 'Sucursales',
            'href'  => 'web.branches.index',
        ],

        // ANALÍTICO
        [
            'type' => 'single',
            'icon' => 'bi bi-graph-up-arrow',
            'label' => 'Analítico',
            'href'  => 'web.analytics.index',
        ],

        // PEDIDOS
        [
            'type' => 'single',
            'icon' => 'bi bi-list-check',
            'label' => 'Pedidos',
            'href'  => 'web.orders.index',
        ],
        [
            'type' => 'single',
            'icon' => 'bi bi-journal-text',
            'label' => 'Registro de Pedidos',
            'href'  => 'web.orders.create-branch',
        ],
    ],
];
