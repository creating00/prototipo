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
                    @canResource('sales.print')
                    <x-adminlte.button color="info" size="sm" icon="fas fa-print" class="me-1 btn-print" />
                    @endcanResource

                    @canResource('sales.view')
                    <x-adminlte.button color="custom-jade" size="sm" icon="fas fa-eye" class="me-1 btn-view" />
                    @endcanResource

                    @canResource('sales.update')
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                    @endcanResource

                    @canResource('sales.delete')
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                    @endcanResource
                </div>
            </x-slot>

            {{-- Botones superiores --}}
            <x-slot name="headerButtons">
                @canResource('repair_amounts.viewAny')
                <a href="{{ route('web.repair-amounts.index') }}" class="btn btn-outline-info me-1">
                    <i class="fas fa-tools me-1"></i> Configurar Montos
                </a>
                @endcanResource
                {{-- Ventas Sucursal → Cliente --}}
                @canResource('sales.create_client')
                <x-adminlte.button color="primary" icon="fas fa-user" class="me-1 btn-header-new-client">
                    Nueva Venta a Cliente
                </x-adminlte.button>
                @endcanResource

                {{-- Ventas Sucursal → Sucursal --}}
                @canResource('sales.create_branch')
                <x-adminlte.button color="custom-emerald" icon="fas fa-building" class="me-1 btn-header-new-branch">
                    Nueva Venta entre Sucursales
                </x-adminlte.button>
                @endcanResource

                {{-- @canResource('repair_amounts.create')
                <x-adminlte.button color="info" icon="fas fa-tools" class="me-1 btn-header-new-repair"
                    data-url="{{ route('web.repair-amounts.index') }}">
                    Nuevo Monto de Reparación
                </x-adminlte.button>
                @endcanResource --}}
            </x-slot>
        </x-adminlte.data-table>
    </div>

    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    @vite('resources/js/modules/sales/index.js')
@endpush
