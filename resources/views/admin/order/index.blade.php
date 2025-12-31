@extends('layouts.app')

@section('page-title', 'Pedidos')

@push('styles')
    <style>
        /* 4 es el valor de OrderStatus::ConvertedToSale */
        tr[data-status_raw="4"] .btn-convert {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        {{-- Alerts --}}
        <x-adminlte.alert-manager />

        {{-- Métricas opcionales (solo si envías $cards desde el controlador) --}}
        @isset($cards)
            <div class="row mb-4">
                @foreach ($cards as $card)
                    <div class="col-md-3 col-sm-6 col-12">
                        <x-adminlte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :description="$card['description'] ?? null"
                            :icon="$card['icon'] ?? ''" :svgPath="$card['svgPath'] ?? ''" :viewBox="$card['viewBox'] ?? '0 0 24 24'" :url="$card['url'] ?? '#'" :customBgColor="$card['customBgColor'] ?? null" />
                    </div>
                @endforeach
            </div>
        @endisset

        <div id="orders-container" data-base-url="{{ route('web.orders.index') }}" data-api-url="/api/orders">
            {{-- DataTable de Pedidos --}}
            <x-adminlte.data-table tableId="orders-table" title="Gestión de Pedidos" :headers="$headers" :rowData="$rowData"
                :hiddenFields="$hiddenFields" withActions="true">
                {{-- Botones en cada fila --}}
                <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" />
                <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                <x-adminlte.button color="success" size="sm" icon="fas fa-file-invoice-dollar"
                    class="me-1 btn-convert" title="Convertir a Venta" />
                <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />

                {{-- Botones superiores --}}
                <x-slot name="headerButtons">
                    {{-- Pedidos Sucursal → Cliente --}}
                    <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client">
                        Nuevo Pedido a Cliente
                    </x-adminlte.button>

                    {{-- En el headerButtons de admin.order.index --}}

                    <x-adminlte.button color="custom-emerald" icon="fas fa-history"
                        class="me-1 btn-header-history-purchase">
                        Mis Pedidos Realizados
                    </x-adminlte.button>

                    {{-- Pedidos Sucursal → Sucursal --}}
                    <x-adminlte.button color="custom-graphite" icon="fas fa-building" class="me-1 btn-header-new-branch">
                        Nuevo Pedido entre Sucursales
                    </x-adminlte.button>
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
@endsection

@include('admin.order.partials._convert_to_sale_modal')

@push('scripts')
    @vite('resources/js/modules/orders/index.js')
@endpush
