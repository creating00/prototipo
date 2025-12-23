@extends('layouts.app')

@section('page-title', 'Productos')

@section('content')
    <div class="container-fluid">
        <x-admin-lte.alert-manager />

        {{-- Componente DataTables --}}
        <x-admin-lte.data-table tableId="products-table" title="Gestión de Productos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">
            {{-- Botones de acción por fila --}}
            @can('products.update')
                <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                    data-route="{{ route('web.products.edit', ['product' => '__row_id__']) }}" />
            @endcan

            @can('products.delete')
                <x-admin-lte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                    data-route="{{ route('web.products.destroy', ['product' => '__row_id__']) }}" />
            @endcan

            {{-- Botón para crear nuevo producto en el encabezado --}}
            <x-slot name="headerButtons">
                @can('products.create')
                    <a href="{{ route('web.products.create') }}">
                        <x-admin-lte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                            Nuevo Producto
                        </x-admin-lte.button>
                    </a>
                @endcan
            </x-slot>
        </x-admin-lte.data-table>
    </div>
@endsection

@push('scripts')
    {{-- Asegúrate de que la ruta del archivo .js sea la correcta para productos --}}
    @vite('resources/js/modules/products/index.js')
@endpush
