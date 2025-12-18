@extends('layouts.app')

@section('page-title', 'Clientes')

@section('content')
    <div class="container-fluid">
        <x-admin-lte.alert-manager />

        <x-admin-lte.data-table tableId="clients-table" title="Gestión de Clientes" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            <x-admin-lte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />

            <x-slot name="headerButtons">
                <x-admin-lte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo Cliente
                </x-admin-lte.button>
            </x-slot>
        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/clients/index.js')
@endpush
