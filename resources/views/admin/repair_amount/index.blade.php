@extends('layouts.app')

@section('page-title', 'Montos de Reparación')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        {{-- Tabla de Montos Activos --}}
        <x-adminlte.data-table tableId="repair-amounts-active-table" title="Precios Vigentes por Sucursal" :headers="$headers"
            :rowData="$activeRows" :hiddenFields="$hiddenFields" withActions="true">

            <x-slot name="actions">
                <div class="d-flex justify-content-center gap-1">
                    @canResource('repair_amounts.update')
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="btn-edit" title="Editar" />
                    @endcanResource
                    @canResource('repair_amounts.delete')
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                        title="Eliminar" />
                    @endcanResource
                </div>
            </x-slot>

            <x-slot name="headerButtons">
                {{-- Botón Volver a Ventas --}}
                <a href="{{ route('web.sales.index') }}" class="btn btn-outline-dark me-2">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Ventas
                </a>
                {{-- Botón para abrir el Historial --}}
                <x-adminlte.button color="secondary" icon="fas fa-history" class="me-1" data-bs-toggle="modal"
                    data-bs-target="#modal-history">
                    Ver Historial
                </x-adminlte.button>

                @canResource('repair_amounts.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="btn-header-new">
                    Nuevo Monto
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>

    {{-- Modal de Historial --}}
    <div class="modal fade" id="modal-history" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gray-dark">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Historial de Cambios de Precios</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Reutilizamos la tabla para el historial --}}
                    <x-adminlte.data-table tableId="repair-amounts-history-table" title="" :headers="$headers"
                        :rowData="$historicalRows" :hiddenFields="$hiddenFields" withActions="false">
                    </x-adminlte.data-table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/repair-amounts/index.js')
@endpush
