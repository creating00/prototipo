@extends('layouts.app')

@section('page-title', 'Proveedores')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />
        <div id="provider-orders-container" data-provider-order-url="{{ route('web.provider-orders.index') }}">
            <x-adminlte.data-table tableId="providers-table" title="Gestión de Proveedores" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

                @canResource('providers.view')
                <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-archive" class="me-1 btn-view" />
                @endcanResource

                @canResource('providers.update')
                <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                @endcanResource

                @canResource('providers.delete')
                <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                @endcanResource

                <x-slot name="headerButtons">
                    @canResource('providers.create')
                    <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                        Nuevo Proveedor
                    </x-adminlte.button>
                    @endcanResource

                    @canResource('provider_orders.create')
                    <x-adminlte.button color="custom-emerald" icon="fas fa-building"
                        class="me-1 btn-header-new-order-provider">
                        Nuevo Pedido a Proveedor
                    </x-adminlte.button>
                    @endcanResource
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/providers/index.js')
@endpush
