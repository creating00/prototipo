@extends('layouts.app')

@section('page-title', 'Sucursales')

@section('content')
    <div class="container-fluid">
        <x-admin-lte.alert-manager />

        <x-admin-lte.data-table 
            tableId="branches-table"
            title="Gestión de Sucursales"
            :headers="$headers"
            :rowData="$rowData"
            :hiddenFields="$hiddenFields"
            withActions="true">

            {{-- Botones por fila --}}
            <x-admin-lte.button 
                color="custom-teal"
                size="sm"
                icon="fas fa-edit"
                class="me-1 btn-edit" />

            <x-admin-lte.button 
                color="danger"
                size="sm"
                icon="fas fa-trash"
                class="btn-delete" />

            {{-- Botón para crear nueva sucursal --}}
            <x-slot name="headerButtons">
                <x-admin-lte.button 
                    color="primary"
                    icon="fas fa-plus"
                    class="me-1 btn-header-new">
                    Nueva Sucursal
                </x-admin-lte.button>
            </x-slot>

        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/branches/index.js')
@endpush
