@extends('layouts.app')

@section('page-title', 'Pedidos')

@push('styles')
    @vite('resources/css/modules/sales/sales-styles.css')
    <style>
        /* Ocultar botón convertir si ya fue convertida (status 4) */
        tr[data-status_raw="4"] .btn-convert {
            display: none !important;
        }

        tr:not([data-customer_type*="Client"]) .btn-whatsapp {
            display: none !important;
        }

        /* Ocultar si el link está vacío o es nulo */
        tr[data-whatsapp-url="null"] .btn-whatsapp,
        tr[data-whatsapp-url=""] .btn-whatsapp {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">

        {{-- Alerts --}}
        <x-adminlte.alert-manager />

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
                <x-slot name="actions">
                    <div class="d-flex justify-content-center gap-1">
                        {{-- Ver detalles --}}
                        @canResource('orders.view')
                        <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" />
                        @endcanResource

                        {{-- WhatsApp (No requiere permiso específico usualmente, pero podrías envolverlo) --}}
                        <x-adminlte.button color="success" size="sm" icon="fab fa-whatsapp" class="me-1 btn-whatsapp"
                            title="Enviar WhatsApp" />

                        {{-- Editar pedido --}}
                        @canResource('orders.update')
                        <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                        @endcanResource

                        {{-- Convertir a venta --}}
                        @canResource('sales.create')
                        <x-adminlte.button color="success" size="sm" icon="fas fa-file-invoice-dollar"
                            class="me-1 btn-convert" title="Convertir a Venta" />
                        @endcanResource

                        {{-- Eliminar / Cancelar --}}
                        @canResource('orders.cancel')
                        <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                        @endcanResource
                    </div>
                </x-slot>

                {{-- Botones superiores --}}
                <x-slot name="headerButtons">
                    @canResource('orders.create_client')
                    {{-- Pedidos Sucursal → Cliente --}}
                    <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client">
                        Nuevo Pedido a Cliente
                    </x-adminlte.button>
                    @endcanResource

                    {{-- Pedidos Sucursal → Sucursal --}}
                    @canResource('orders.create_branch')
                    <x-adminlte.button color="custom-graphite" icon="fas fa-building" class="me-1 btn-header-new-branch">
                        Nuevo Pedido entre Sucursales
                    </x-adminlte.button>
                    @endcanResource

                    @canResource('orders.view_own')
                    <x-adminlte.button color="custom-emerald" icon="fas fa-history"
                        class="me-1 btn-header-history-purchase">
                        Mis Pedidos Realizados
                    </x-adminlte.button>
                    @endcanResource
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
@endsection

@include('admin.order.partials._convert_to_sale_modal')

@push('scripts')
    @vite('resources/js/modules/orders/index.js')
@endpush
