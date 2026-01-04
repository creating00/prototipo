@extends('layouts.app')

@section('page-title', 'Auditoría de Precios')

@section('content')
    {{-- BLOQUE DE FILTROS --}}
    <x-adminlte.card title="Filtros de Auditoría" type="white">
        <x-slot:tools>
            <a href="{{ route('web.price-modifications.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sync-alt mr-1"></i> Restablecer
            </a>
        </x-slot:tools>

        <form method="GET" action="{{ route('web.price-modifications.index') }}">
            <div class="row align-items-end">
                {{-- Filtro de Sucursal --}}
                <div class="col-md-4">
                    <label class="compact-select-label">Sucursal</label>
                    <x-adminlte.select name="branch_id" :options="$branches" :value="$currentFilters['branch_id']" :showPlaceholder="false"
                        onchange="this.form.submit()" />
                </div>

                {{-- Filtro de Fecha Desde --}}
                <div class="col-md-4">
                    <x-bootstrap.compact-input id="start_date" name="start_date" type="date" label="Desde"
                        value="{{ $currentFilters['start_date'] }}" onchange="this.form.submit()" />
                </div>

                {{-- Filtro de Fecha Hasta --}}
                <div class="col-md-4">
                    <x-bootstrap.compact-input id="end_date" name="end_date" type="date" label="Hasta"
                        value="{{ $currentFilters['end_date'] }}" onchange="this.form.submit()" />
                </div>
            </div>
        </form>
    </x-adminlte.card>

    {{-- TABLA DE RESULTADOS --}}
    <div class="row">
        <div class="col-12">
            <x-adminlte.data-table tableId="price-audit-table" title="Historial de Modificaciones" :headers="$headers"
                :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="false">

                {{-- Sin botones de acción según requerimiento --}}

            </x-adminlte.data-table>
        </div>
    </div>
@endsection
