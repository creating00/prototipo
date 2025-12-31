@extends('layouts.app')

@section('page-title', 'Usuarios')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        {{-- Componente DataTables para Usuarios --}}
        <x-adminlte.data-table tableId="users-table" title="Gestión de Usuarios del Sistema" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            {{-- Botones de acción por fila --}}
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                data-route="{{ route('web.users.edit', ['user' => '__row_id__']) }}" />

            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                data-route="{{ route('web.users.destroy', ['user' => '__row_id__']) }}" />

            {{-- Botón para crear nuevo usuario en el encabezado --}}
            <x-slot name="headerButtons">
                <a href="{{ route('web.users.create') }}">
                    <x-adminlte.button color="primary" icon="fas fa-user-plus" class="me-1 btn-header-new">
                        Nuevo Usuario
                    </x-adminlte.button>
                </a>
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    {{-- Script específico para la lógica de DataTables y eliminación de usuarios --}}
    @vite('resources/js/modules/users/index.js')
@endpush
