<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('page-title', config('app.name', 'Laravel'))</title>

    <meta name="supported-color-schemes" content="light dark" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/adminlte-components.js'
    ])

    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!-- App Wrapper -->
    <div class="app-wrapper">

        <!-- Header -->
        @include('adminlte.partials.header')

        <!-- Sidebar -->
        @include('adminlte.partials.sidebar')

        <!-- Main Content -->
        <main class="app-main">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('adminlte.partials.footer')

    </div>

    @stack('scripts')
</body>
</html>
