@extends('layouts.app')

@section('page-title', 'Ventas')

@section('content')
    <div class="container-fluid">

        {{-- Alerts --}}
        <x-adminlte.alert-manager />

        {{-- Métricas opcionales (solo si envías $cards desde el controlador) --}}
        @isset($cards)
            <div class="row mb-4">
                @foreach ($cards as $card)
                    <div class="col-md-3 col-sm-6 col-12">
                        <x-adminlte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :description="$card['description'] ?? null"
                            :icon="$card['icon'] ?? ''" :svgPath="$card['svgPath'] ?? ''" :viewBox="$card['viewBox'] ?? '0 0 24 24'" :url="$card['url'] ?? '#'" :customBgColor="$card['customBgColor'] ?? null" />
                    </div>
                @endforeach
            </div>
        @endisset

        {{-- DataTable de Ventas --}}
        <x-adminlte.data-table tableId="sales-table" title="Gestión de Ventas" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones en cada fila --}}

            <x-slot name="actions">
                <div class="d-flex justify-content-center gap-1">
                    <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" />
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                </div>
            </x-slot>

            {{-- Botones superiores --}}
            <x-slot name="headerButtons">
                {{-- Ventas Sucursal → Cliente --}}
                <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client">
                    Nueva Venta a Cliente
                </x-adminlte.button>

                {{-- Ventas Sucursal → Sucursal --}}
                <x-adminlte.button color="custom-emerald" icon="fas fa-building" class="me-1 btn-header-new-branch">
                    Nueva Venta entre Sucursales
                </x-adminlte.button>
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/sales/index.js')
@endpush
