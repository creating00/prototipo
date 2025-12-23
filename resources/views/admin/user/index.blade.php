@extends('layouts.app')

@section('page-title', 'Usuarios')

@section('content')
    <div class="container-fluid">
        <x-admin-lte.alert-manager />

        {{-- Componente DataTables para Usuarios --}}
        <x-admin-lte.data-table tableId="users-table" title="Gestión de Usuarios del Sistema" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            {{-- Botones de acción por fila --}}
            <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                data-route="{{ route('web.users.edit', ['user' => '__row_id__']) }}" />

            <x-admin-lte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                data-route="{{ route('web.users.destroy', ['user' => '__row_id__']) }}" />

            {{-- Botón para crear nuevo usuario en el encabezado --}}
            <x-slot name="headerButtons">
                <a href="{{ route('web.users.create') }}">
                    <x-admin-lte.button color="primary" icon="fas fa-user-plus" class="me-1 btn-header-new">
                        Nuevo Usuario
                    </x-admin-lte.button>
                </a>
            </x-slot>
        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    {{-- Script específico para la lógica de DataTables y eliminación de usuarios --}}
    @vite('resources/js/modules/users/index.js')
@endpush
