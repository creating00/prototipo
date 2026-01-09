@extends('layouts.app')

@section('page-title', 'Gastos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="expenses-table" title="Gestión de Gastos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones por fila --}}
            @canResource('expenses.update')
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            @endcanResource

            @canResource('expenses.delete')
            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
            @endcanResource

            {{-- Botón para crear nueva gasto --}}
            <x-slot name="headerButtons">
                @canResource('expenses.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo Gasto
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/expenses/index.js')
@endpush
