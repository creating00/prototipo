@extends('layouts.app')

@section('page-title', 'Mis Pedidos a Sucursales')

@section('content')
    <div class="container-fluid">

        {{-- Alertas de sistema --}}
        <x-adminlte.alert-manager />

        {{-- DataTable de Compras Realizadas --}}
        <x-adminlte.data-table tableId="purchases-table" title="Pedidos realizados a otras sucursales" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            {{-- Solo botón de Ver e Imprimir --}}
            <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" title="Ver Detalle" />
            <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="btn-print"
                title="Imprimir Comprobante" />

            {{-- Botones superiores --}}
            <x-slot name="headerButtons">
                {{-- Botón para regresar al index principal de ventas --}}
                <x-adminlte.button color="secondary" icon="fas fa-arrow-left" class="me-1 btn-header-back"
                    onclick="window.location.href='{{ route('web.orders.index') }}'">
                    Volver a Gestión de Ventas
                </x-adminlte.button>

                {{-- Botón para iniciar un nuevo pedido (opcional) --}}
                <x-adminlte.button color="primary" icon="fas fa-plus" class="btn-header-new-branch"
                    onclick="window.location.href='{{ route('web.orders.create-branch') }}'">
                    Nuevo Pedido a Sucursal
                </x-adminlte.button>
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    {{-- Usaremos un JS específico o el mismo index.js si lo parametrizas --}}
    @vite('resources/js/modules/orders/purchases.js')
@endpush
