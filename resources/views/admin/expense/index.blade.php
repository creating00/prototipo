@extends('layouts.app')

@section('page-title', 'Gastos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />
        {{-- Sección de Plantillas de Gastos --}}
        {{-- <x-bootstrap.import-center title="Centro de Importación de Gastos"
            description="Utiliza la plantilla oficial para evitar errores en el procesamiento de tus comprobantes.">

            <a href="{{ route('web.expenses.template') }}" class="btn btn-sm btn-outline-primary btn-download-template"
                data-type="gastos">
                <i class="fas fa-download me-1"></i> Descargar Plantilla Gastos
            </a>
        </x-bootstrap.import-center> --}}

        <div id="expenses-table-wrapper" data-current-branch-id="{{ $currentBranchId }}">
            <x-adminlte.data-table tableId="expenses-table" title="Gestión de Gastos" size="sm-expenses" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">


                {{-- Botones en cada fila --}}
                <x-slot name="actions">
                    @canResource('expenses.update')
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />
                    @endcanResource

                    @canResource('expenses.delete')
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete" />
                    @endcanResource
                </x-slot>

                {{-- Botón para crear nueva gasto --}}
                <x-slot name="headerButtons">
                    @canResource('expenses.create')
                    {{-- Botón de Importar --}}
                    {{-- <x-adminlte.button color="success" icon="fas fa-file-import" class="me-1 btn-header-import-expenses">
                        Importar Gastos
                    </x-adminlte.button> --}}

                    {{-- Botón de Nuevo Gasto --}}
                    <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                        Nuevo Gasto
                    </x-adminlte.button>

                    {{-- Input oculto para la importación --}}
                    <input type="file" id="import-expenses-excel-input" style="display: none;"
                        accept=".xlsx, .xls, .csv">
                    @endcanResource
                </x-slot>

                <x-slot name="body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <x-bootstrap.select name="filter_branch" id="filter-branch" :options="$branches"
                                    placeholder="Todas las Sucursales" class="form-select-sm d-none"
                                    container-class="mb-0" />
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
                                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total ARS
                                </div>
                                <span id="total-ars" class="fw-bold text-danger fs-5">$ 0,00</span>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Total USD
                                </div>
                                <span id="total-usd" class="fw-bold text-primary fs-5">U$D 0,00</span>
                            </div>
                        </div>
                    </div>
                </x-slot>
            </x-adminlte.data-table>
        </div>
    </div>
    <template id="expense-actions-locked-template">
        <span class="text-muted" title="No permitido para esta sucursal">
            <i class="fas fa-lock"></i>
        </span>
    </template>
@endsection

@push('scripts')
    @vite('resources/js/modules/expenses/index.js')
@endpush
