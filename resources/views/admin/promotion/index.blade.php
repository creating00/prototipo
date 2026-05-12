@extends('layouts.app')

@section('page-title', 'Promociones')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="promotions-table" title="Gestión de Banners y Promociones" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">
            {{-- Acciones por fila --}}
            @canResource('promotions.update')
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            @endcanResource

            @canResource('promotions.delete')
            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
            @endcanResource

            {{-- Botón superior --}}
            <x-slot name="headerButtons">
                @canResource('promotions.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nueva Promoción
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/promotions/index.js')
@endpush
