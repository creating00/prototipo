<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--begin::Head-->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <meta name="supported-color-schemes" content="light dark" />
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page-title', 'App')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/adminlte-components.js'])
    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        <x-adminlte.header />
        <!--end::Header-->
        <!--begin::Sidebar-->
        <x-adminlte.sidebar />
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <x-admin-lte.app-main>
            @yield('content')
        </x-admin-lte.app-main>

        <!--end::App Main-->
        <!--begin::Footer-->
        <x-adminlte.footer />
        <!--end::Footer-->
    </div>
    @stack('scripts')
</body>
<!--end::Body-->

</html>
