@extends('layouts.app')

@section('page-title', 'Productos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

        {{-- Componente DataTables --}}
        <x-adminlte.data-table tableId="products-table" title="Gestión de Productos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones de acción por fila --}}
            @can('products.update')
                <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                    data-route="{{ route('web.products.edit', ['product' => '__row_id__']) }}" />
            @endcan

            @can('products.delete')
                <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                    data-route="{{ route('web.products.destroy', ['product' => '__row_id__']) }}" />
            @endcan

            {{-- Botón para crear nuevo producto en el encabezado --}}
            <x-slot name="headerButtons">
                @can('products.create')
                    <a href="{{ route('web.products.create') }}">
                        <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                            Nuevo Producto
                        </x-adminlte.button>
                    </a>
                @endcan
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/products/index.js')
@endpush
