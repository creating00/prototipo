@extends('layouts.app')

@section('page-title', 'Órdenes de Compra (Proveedores)')

@push('styles')
    <style>
        tr[data-status_raw="4"] .btn-receive {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <div id="provider-orders-container" data-base-url="{{ route('web.provider-orders.index') }}"
            data-api-url="/api/provider-orders">

            <x-adminlte.data-table tableId="provider-orders-table" title="Pedidos a Proveedores" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

                {{-- Botones de acción por fila --}}
                <x-slot name="actions">
                    <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="btn-view"
                        title="Ver Detalle" />
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="btn-edit"
                        title="Editar Borrador" />
                    <x-adminlte.button color="success" size="sm" icon="fas fa-truck" class="btn-receive"
                        title="Marcar como Recibido" />
                </x-slot>

                <x-slot name="headerButtons">
                    <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-provider-order">
                        Nueva Orden de Compra
                    </x-adminlte.button>
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/provider-order/index.js')
@endpush
