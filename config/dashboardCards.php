<?php

return [
    'cards' => [
        'orders' => [
            'title' => 'New Orders',
            'value' => 150,
            'color' => 'primary',
            'icon' => 'cart',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/orders',
            'description' => 'Total orders today'
        ],
        'revenue' => [
            'title' => 'Revenue',
            'value' => '$2,340',
            'color' => 'success',
            'icon' => 'dollar',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/revenue',
            'description' => 'Monthly earnings'
        ],
        'users' => [
            'title' => 'User Registrations',
            'value' => 44,
            'color' => 'warning',
            'icon' => 'users',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/users',
            'description' => 'New users this week'
        ],
        'tickets' => [
            'title' => 'Support Tickets',
            'value' => 12,
            'color' => 'custom',
            'customBgColor' => "#69e76fff",
            'icon' => 'ticket',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/tickets',
            'description' => 'Pending tickets'
        ],
        'products' => [
            'title' => 'Total Products',
            'value' => 856,
            'color' => 'info',
            'icon' => 'box',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/products',
            'description' => 'Active products'
        ],
        'visitors' => [
            'title' => 'Website Visitors',
            'value' => '3.2K',
            'color' => 'secondary',
            'icon' => 'analytics',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/analytics',
            'description' => 'Today visitors'
        ],
        'messages' => [
            'title' => 'Unread Messages',
            'value' => 23,
            'color' => 'custom',
            'customBgColor' => "#6f42c1",
            'icon' => 'email',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/messages',
            'description' => 'New messages'
        ],
        'growth' => [
            'title' => 'Growth Rate',
            'value' => '65%',
            'color' => 'danger',
            'icon' => 'trending',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/growth',
            'description' => 'This month'
        ],
        'storage' => [
            'title' => 'Storage Used',
            'value' => '78%',
            'color' => 'custom',
            'customBgColor' => "#fd7e14",
            'icon' => 'storage',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/storage',
            'description' => 'Cloud storage'
        ],
        'downloads' => [
            'title' => 'Total Downloads',
            'value' => '1.2K',
            'color' => 'custom',
            'customBgColor' => "#20c997",
            'icon' => 'download',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/downloads',
            'description' => 'This week'
        ],
        'performance' => [
            'title' => 'Performance',
            'value' => '92%',
            'color' => 'custom',
            'customBgColor' => "#6610f2",
            'icon' => 'performance',
            'viewBox' => '0 0 24 24',
            'url' => '/admin/performance',
            'description' => 'System performance'
        ],
    ],

    'colors' => [
        'primary' => 'text-bg-primary',
        'success' => 'text-bg-success',
        'warning' => 'text-bg-warning',
        'danger' => 'text-bg-danger',
        'info' => 'text-bg-info',
        'secondary' => 'text-bg-secondary',
    ],

];
