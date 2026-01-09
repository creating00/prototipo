@extends('layouts.app')

@section('page-title', 'Sucursales')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="branches-table" title="Gestión de Sucursales" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones por fila --}}
            <x-slot name="actions">
                <div class="d-flex justify-content-center gap-1">
                    @canResource('branches.update')
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                        title="Editar Sucursal" />
                    @endcanResource

                    @canResource('branches.delete')
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                        title="Eliminar Sucursal" />
                    @endcanResource
                </div>
            </x-slot>

            {{-- Botón para crear nueva sucursal --}}
            <x-slot name="headerButtons">
                @canResource('branches.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nueva Sucursal
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/branches/index.js')
@endpush
