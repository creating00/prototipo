@extends('layouts.app')

@section('page-title', 'Descuentos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="discounts-table" title="Gestión de Descuentos y Promociones" :headers="$headers"
            :rowData="$rowData" withActions="true">

            {{-- Botones por fila (Edit y Delete) --}}
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                data-tooltip="Editar Descuento" />

            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                data-tooltip="Eliminar Descuento" />

            {{-- Botón para crear nuevo descuento --}}
            <x-slot name="headerButtons">
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo Descuento
                </x-adminlte.button>
            </x-slot>

        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/discounts/index.js')
@endpush
