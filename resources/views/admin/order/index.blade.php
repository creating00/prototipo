@extends('layouts.app')

@section('page-title', 'Pedidos')

@push('styles')
    @vite('resources/css/modules/sales/sales-styles.css')
    {{-- @vite('resources/css/modules/sales/payment-dual-styles.css') --}}
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

        /* Ocultar botón de impresión por defecto */
        .btn-print {
            display: none !important;
        }

        /* Mostrar solo cuando el status es 4 (Convertido) */
        tr[data-status_raw="4"] .btn-print {
            display: inline-block !important;
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

        <div id="orders-container" data-base-url="{{ route('web.orders.index') }}"
            data-sale-url="{{ route('web.sales.index') }}" data-api-url="/api/orders">
            {{-- DataTable de Pedidos --}}
            <x-adminlte.data-table tableId="orders-table" title="Gestión de Pedidos" size="sm-orders" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

                <x-slot name="body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-3">

                            <div class="d-flex align-items-center gap-2">
                                <x-bootstrap.select name="filter_status" id="filter-status" :options="\App\Enums\OrderStatus::forOrder()"
                                    placeholder="Todos los Estados" class="form-select-sm" container-class="mb-0" />

                                <x-bootstrap.select name="filter_source" id="filter-source" :options="\App\Enums\OrderSource::forSelect()"
                                    placeholder="Todos los Orígenes" class="form-select-sm" container-class="mb-0" />
                            </div>

                            <div class="vr d-none d-lg-block" style="height: 20px; opacity: 0.2;"></div>

                            <div class="position-relative">
                                <input type="text" id="filter-month" class="form-control form-control-sm bg-white ps-4"
                                    style="min-width: 150px;" placeholder="Seleccionar mes" readonly>
                                <i class="bi bi-calendar3 position-absolute start-2 top-50 translate-middle-y text-muted"
                                    style="left: 10px; font-size: 0.8rem; pointer-events: none;"></i>
                            </div>

                            <button type="button" id="btn-reset-filters"
                                class="btn btn-link btn-sm text-decoration-none p-0 text-muted">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                        @canResource('order.view_money')
                        <div class="d-flex align-items-center gap-3 border-start ps-3">
                            <div class="text-end">
                                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total ARS
                                </div>
                                <span id="total-ars" class="fw-bold text-success fs-5">$ 0,00</span>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total USD
                                </div>
                                <span id="total-usd" class="fw-bold text-primary fs-5">U$D 0,00</span>
                            </div>
                        </div>
                        @endcanResource
                    </div>
                </x-slot>

                {{-- Botones en cada fila --}}
                <x-slot name="actions">
                    <div class="d-flex justify-content-center gap-1">
                        @canResource('sales.print')
                        <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="me-1 btn-print" />
                        @endcanResource
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
                        @canResource('sales.create_client')
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
                    <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client d-none">
                        Nuevo Pedido a Cliente
                    </x-adminlte.button>
                    @endcanResource

                    {{-- Pedidos Sucursal → Sucursal --}}
                    @canResource('orders.create_branch')
                    {{-- custom-graphite --}}
                    <x-adminlte.button color="primary" icon="fas fa-building" class="me-1 btn-header-new-branch">
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
    @include('admin.order.partials._convert_to_sale_modal')
    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    <script>
        // Definimos la tasa globalmente para los módulos JS
        window.currentExchangeRate = {{ $currentRate }};
    </script>
    @vite('resources/js/modules/orders/index.js')
@endpush

<x-sale-print-handler />
