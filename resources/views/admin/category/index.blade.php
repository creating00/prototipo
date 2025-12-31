@extends('layouts.app')

@section('page-title', 'Categorías')

@push('styles')
    <style>
        /* Ocultar botones en filas de sistema */
        tr[data-is_system="1"] .btn-edit,
        tr[data-is_system="1"] .btn-delete {
            display: none !important;
        }

        /* Mostrar un candado para indicar que está protegido */
        tr[data-is_system="1"] td.text-center::before {
            content: "\f023";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            color: #adb5bd;
            margin-right: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="categories-table" title="Gestión de Categorías" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />

            <x-slot name="headerButtons">
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nueva Categoría
                </x-adminlte.button>
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/categories/index.js')
@endpush
