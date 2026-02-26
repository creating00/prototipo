@extends('layouts.app')

@section('page-title', 'Productos')

@section('content')
    <div class="container-fluid">
        {{-- Inputs ocultos --}}
        <input type="file" id="import-excel-input" style="display: none;" accept=".xlsx, .xls, .csv">
        <input type="file" id="import-providers-excel-input" style="display: none;" accept=".xlsx, .xls, .csv">

        <x-adminlte.alert-manager />

        {{-- Sección de Plantillas --}}
        <x-bootstrap.import-center>
            <a href="{{ route('web.products.template') }}" class="btn btn-sm btn-outline-primary btn-download-template"
                data-type="productos">
                <i class="fas fa-download me-1"></i> Plantilla Productos
            </a>

            <a href="{{ route('web.providers.template') }}" class="btn btn-sm btn-outline-primary btn-download-template"
                data-type="proveedores">
                <i class="fas fa-download me-1"></i> Plantilla Proveedores
            </a>
        </x-bootstrap.import-center>

        <x-adminlte.data-table tableId="products-table" title="Gestión de Productos" :headers="$headers" :rowData="$rowData"
            :hiddenFields="$hiddenFields" withActions="true">

            {{-- Botones por fila --}}
            <x-slot name="actions">
                <div class="d-flex justify-content-center gap-1">
                    @canResource('products.update')
                    <x-adminlte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit"
                        data-route="{{ route('web.products.edit', ['product' => '__row_id__']) }}" />
                    @endcanResource

                    @canResource('products.delete')
                    <x-adminlte.button color="danger" size="sm" icon="fas fa-trash" class="btn-delete"
                        data-route="{{ route('web.products.destroy', ['product' => '__row_id__']) }}" />
                    @endcanResource
                </div>
            </x-slot>

            {{-- Botones de cabecera --}}
            <x-slot name="headerButtons">
                @canResource('products.create')
                {{-- Botón de Importar --}}
                <x-adminlte.button color="success" icon="fas fa-file-import" class="me-1 btn-header-import">
                    Importar Productos
                </x-adminlte.button>

                <x-adminlte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-new">
                    Nuevo Producto
                </x-adminlte.button>
                @endcanResource

                @canResource('providers.create')
                {{-- Botón de Importar Proveedores --}}
                <x-adminlte.button color="success" icon="fas fa-file-import" class="me-1 btn-header-import-providers">
                    Importar Proveedores
                </x-adminlte.button>

                <x-adminlte.button color="custom-dark-blue" icon="fas fa-plus" class="me-1 btn-header-new-provider">
                    Nuevo Proveedor
                </x-adminlte.button>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
    @include('admin.provider.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/products/index.js')
@endpush
