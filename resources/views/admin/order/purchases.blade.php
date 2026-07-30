@extends('layouts.app')

@section('page-title', 'Mis Pedidos a Sucursales')

@push('styles')
    <style>
        /* 1. Ocultar botón receive si ya fue enviado al stock / recibido */
        .btn-receive {
            display: none !important;
        }

        tr[data-is_received="false"] .btn-receive {
            display: inline-block !important;
        }

        /* 2. Ocultar botón editar si ya fue enviado al stock (can_edit = false) */
        tr[data-can_edit="false"] .btn-edit {
            display: none !important;
        }

        /* 3. Reglas de WhatsApp (sin cambios) */
        tr[data-whatsapp-url="null"] .btn-whatsapp,
        tr[data-whatsapp-url=""] .btn-whatsapp {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Alertas de sistema --}}
        <x-adminlte.alert-manager />

        <div id="orders-container" data-base-url-origin="{{ route('web.orders.index') }}">
            {{-- DataTable de Compras Realizadas --}}
            <x-adminlte.data-table tableId="purchases-table" title="Pedidos realizados a otras sucursales" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

                <x-slot name="actions">
                    <div class="d-flex justify-content-center gap-1">
                        <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="btn-view"
                            title="Ver detalles" />
                        <x-adminlte.button color="warning" size="sm" icon="fas fa-edit" class="btn-edit"
                            title="Editar Pedido" />
                        <x-adminlte.button color="success" size="sm" icon="fas fa-check-double" class="btn-receive"
                            title="Enviar al Stock / Recibir" />
                        <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="btn-print"
                            title="Imprimir" />
                    </div>
                </x-slot>

                {{-- Botones superiores --}}
                <x-slot name="headerButtons">
                    {{-- Botón para regresar al index principal de ventas --}}
                    <x-adminlte.button color="secondary" icon="fas fa-arrow-left" class="me-1 btn-header-back"
                        onclick="window.location.href='{{ route('web.orders.index') }}'">
                        Volver a Gestión de Ventas
                    </x-adminlte.button>

                    {{-- Botón para iniciar un nuevo pedido a sucursal --}}
                    <x-adminlte.button color="primary" icon="fas fa-plus" class="btn-header-new-branch"
                        onclick="window.location.href='{{ route('web.orders.create-branch') }}'">
                        Nuevo Pedido a Sucursal
                    </x-adminlte.button>
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/orders/purchases.js')
@endpush
