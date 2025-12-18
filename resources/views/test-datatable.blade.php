@extends('layouts.app')

@section('title', 'Prueba DataTable')

@section('content')
    <div class="container-fluid">
        @php
            $rowData = [
                [
                    'id' => 1,
                    'nombre' => 'Juan Pérez',
                    'email' => 'juan@example.com',
                    'rol' => 'Admin',
                    'fecha' => '2024-01-15',
                ],
                [
                    'id' => 2,
                    'nombre' => 'María García',
                    'email' => 'maria@example.com',
                    'rol' => 'Usuario',
                    'fecha' => '2024-01-16',
                ],
                [
                    'id' => 3,
                    'nombre' => 'Carlos López',
                    'email' => 'carlos@example.com',
                    'rol' => 'Editor',
                    'fecha' => '2024-01-17',
                ],
            ];
        @endphp

        <x-admin-lte.data-table tableId="test-table" title="Prueba de DataTable con Botones" :headers="['ID', 'Nombre', 'Email', 'Rol', 'Fecha']"
            :rowData="$rowData" withActions="true">
            <!-- Botones de acciones -->
            <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            <x-admin-lte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />

            <!-- Botones del header -->
            <x-slot name="headerButtons">
                <x-admin-lte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo
                </x-admin-lte.button>
                <x-admin-lte.button color="danger" icon="fas fa-trash-restore" class="me-1 btn-header-deleted">
                    Eliminados
                </x-admin-lte.button>
                <x-admin-lte.button color="custom-indigo" outline="true" icon="fas fa-print" class="btn-header-print">
                    Imprimir
                </x-admin-lte.button>
            </x-slot>
        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/users/index.js')
@endpush
