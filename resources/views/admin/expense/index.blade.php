@extends('layouts.app')

@section('page-title', 'Gastos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        <x-adminlte.data-table tableId="expenses-table" title="Gestión de Gastos" size="sm-expenses" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones por fila --}}
            @canResource('expenses.update')
            <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
            @endcanResource

            @canResource('expenses.delete')
            <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
            @endcanResource

            {{-- Botón para crear nueva gasto --}}
            <x-slot name="headerButtons">
                @canResource('expenses.create')
                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo Gasto
                </x-adminlte.button>
                @endcanResource
            </x-slot>

            <x-slot name="body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
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
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/expenses/index.js')
@endpush
