@extends('layouts.app')

@section('page-title', 'Pedidos')

@section('content')
    <div class="container-fluid">

        {{-- Alerts --}}
        <x-admin-lte.alert-manager />

        {{-- Métricas opcionales (solo si envías $cards desde el controlador) --}}
        @isset($cards)
            <div class="row mb-4">
                @foreach ($cards as $card)
                    <div class="col-md-3 col-sm-6 col-12">
                        <x-admin-lte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :description="$card['description'] ?? null"
                            :icon="$card['icon'] ?? ''" :svgPath="$card['svgPath'] ?? ''" :viewBox="$card['viewBox'] ?? '0 0 24 24'" :url="$card['url'] ?? '#'" :customBgColor="$card['customBgColor'] ?? null" />
                    </div>
                @endforeach
            </div>
        @endisset

        {{-- DataTable de Pedidos --}}
        <x-admin-lte.data-table tableId="orders-table" title="Gestión de Pedidos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones en cada fila --}}
            <x-admin-lte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" />
            <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            <x-admin-lte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />

            {{-- Botones superiores --}}
            <x-slot name="headerButtons">
                {{-- Pedidos Sucursal → Cliente --}}
                <x-admin-lte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client">
                    Nuevo Pedido a Cliente
                </x-admin-lte.button>

                {{-- Pedidos Sucursal → Sucursal --}}
                <x-admin-lte.button color="custom-emerald" icon="fas fa-building" class="me-1 btn-header-new-branch">
                    Nuevo Pedido entre Sucursales
                </x-admin-lte.button>
            </x-slot>
        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/orders/index.js')
@endpush
