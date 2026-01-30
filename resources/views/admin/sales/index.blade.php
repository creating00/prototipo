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
        <x-adminlte.data-table tableId="sales-table" title="Gestión de Ventas" size="sm-sales" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">

            {{-- Botones superiores --}}
            <x-slot name="headerButtons">
                <div class="d-flex align-items-center flex-wrap gap-2">

                    {{-- @canResource('repair_amounts.viewAny')
                    <a href="{{ route('web.repair-amounts.index') }}" class="btn btn-outline-info me-1">
                        <i class="fas fa-tools me-1"></i> Configurar Montos
                    </a>
                    @endcanResource --}}

                    <a href="{{ route('web.banks.index') }}" class="btn btn-outline-info me-1">
                        <i class="fas fa-tools me-1"></i> Configurar Bancos
                    </a>

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
                </div>

                {{-- @canResource('repair_amounts.create')
                <x-adminlte.button color="info" icon="fas fa-tools" class="me-1 btn-header-new-repair"
                    data-url="{{ route('web.repair-amounts.index') }}">
                    Nuevo Monto de Reparación
                </x-adminlte.button>
                @endcanResource --}}
            </x-slot>

            <x-slot name="body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <x-bootstrap.select name="filter_type" id="filter-type" :options="\App\Enums\SaleType::forSelect()"
                                placeholder="Todos los Tipos" class="form-select-sm" container-class="mb-0" />

                            <x-bootstrap.select name="filter_payment" id="filter-payment" :options="\App\Enums\PaymentType::forSelect()"
                                placeholder="Todos los Pagos" class="form-select-sm" container-class="mb-0" />
                        </div>

                        <div class="vr d-none d-lg-block" style="height: 20px; opacity: 0.2;"></div>

                        <div class="position-relative">
                            <input type="text" id="filter-month" class="form-control form-control-sm bg-white ps-4"
                                style="min-width: 150px;" placeholder="Seleccionar mes" readonly>
                            <i class="bi bi-calendar3 position-absolute start-2 top-50 translate-middle-y text-muted"
                                style="left: 10px; font-size: 0.8rem; pointer-events: none;"></i>
                        </div>

                        <div class="form-check form-switch border-start ps-5 mb-0">
                            <input class="form-check-input" type="checkbox" id="filter-invoice" role="switch">
                            <label class="form-check-label small text-secondary fw-bold" for="filter-invoice">
                                SOLO FACTURAR
                            </label>
                        </div>

                        <button type="button" id="btn-reset-filters"
                            class="btn btn-link btn-sm text-decoration-none p-0 text-muted">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-3 border-start ps-3">
                        <div class="text-end">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total ARS</div>
                            <span id="total-ars" class="fw-bold text-success fs-5">$ 0,00</span>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total USD</div>
                            <span id="total-usd" class="fw-bold text-primary fs-5">U$D 0,00</span>
                        </div>
                    </div>
                </div>
            </x-slot>

            {{-- <x-slot name="footer">
               
            </x-slot> --}}

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
        </x-adminlte.data-table>
    </div>

    @include('admin.sales.partials._modal-print')
@endsection

@push('scripts')
    @vite('resources/js/modules/sales/index.js')
@endpush
