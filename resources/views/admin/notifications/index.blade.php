@extends('layouts.app')

@section('page-title', 'Notificaciones')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="notifications-table" title="Centro de Notificaciones" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones por fila --}}
            <x-slot name="actions">
                <div class="d-flex justify-content-center gap-1">
                    {{-- <x-adminlte.button color="info" size="sm" icon="fas fa-eye" class="me-1 btn-view"
                        title="Ver Pedido" /> --}}

                    <x-adminlte.button color="success" size="sm" icon="fas fa-check" class="me-1 btn-mark-read"
                        title="Marcar como leída" />

                    <button class="btn btn-sm btn-secondary read-indicator d-none" disabled title="Ya leída">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </x-slot>

            {{-- Botón general --}}
            <x-slot name="headerButtons">
                <x-adminlte.button color="primary" icon="fas fa-check-double" class="me-1 btn-header-mark-all">
                    Marcar todas como leídas
                </x-adminlte.button>
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    {{-- Requiere crear este archivo para la logica de DataTable y peticiones AJAX --}}
    @vite('resources/js/modules/notifications/index.js')
@endpush
