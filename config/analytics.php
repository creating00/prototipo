<?php

return [

    'infoboxes' => [

        'sales_today' => [
            'icon'  => 'bi bi-receipt',
            'color' => 'primary',
            'text'  => 'Ventas hoy',
        ],

        'sales_month' => [
            'icon'  => 'bi bi-calendar-month',
            'color' => 'info',
            'text'  => 'Ventas del mes',
        ],

        'sales_year' => [
            'icon'  => 'bi bi-calendar3',
            'color' => 'secondary',
            'text'  => 'Ventas del año',
        ],

    ],
    'expense_infoboxes' => [

        'expenses_today' => [
            'icon'  => 'bi bi-wallet2',
            'color' => 'danger',
            'text'  => 'Gastos hoy',
            'prefix' => '$',
        ],

        'expenses_month' => [
            'icon'  => 'bi bi-wallet',
            'color' => 'warning',
            'text'  => 'Gastos del mes',
            'prefix' => '$',
        ],

        'expenses_year' => [
            'icon'  => 'bi bi-wallet-fill',
            'color' => 'secondary',
            'text'  => 'Gastos del año',
            'prefix' => '$',
        ],
    ],
    'result_infoboxes' => [
        'net_month' => [
            'icon'  => 'bi bi-calculator',
            'color' => 'success',
            'text'  => 'Resultado del mes',
            'prefix' => '$',
        ],
        'net_year' => [
            'icon'  => 'bi bi-calculator-fill',
            'color' => 'success',
            'text'  => 'Resultado del año',
            'prefix' => '$',
        ],
    ],
];
