@extends('layouts.app')

@section('page-title', 'Cuentas Bancarias')

@push('styles')
    <style>
        /* Ocultar botones en filas protegidas (si aplica en el futuro) */
        tr[data-is_system="1"] .btn-edit,
        tr[data-is_system="1"] .btn-delete {
            display: none !important;
        }

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

        <x-adminlte.data-table tableId="bank-accounts-table" title="Gestión de Cuentas Bancarias" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            <x-slot name="actions">
                @canResource('bank-accounts.update')
                <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                @endcanResource

                @canResource('bank-accounts.delete')
                <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                @endcanResource
            </x-slot>

            <x-slot name="headerButtons">
                @canResource('bank-accounts.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nueva Cuenta
                </x-adminlte.button>
                @endcanResource
            </x-slot>

        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/bank-accounts/index.js')
@endpush
