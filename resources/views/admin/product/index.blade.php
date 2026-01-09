@extends('layouts.app')

@section('page-title', 'Productos')

@section('content')
    <div class="container-fluid">
        <x-adminlte.alert-manager />

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
                <a href="{{ route('web.products.create') }}">
                    <x-adminlte.button color="primary" icon="fas fa-plus">
                        Nuevo Producto
                    </x-adminlte.button>
                </a>
                @endcanResource
            </x-slot>
        </x-adminlte.data-table>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/products/index.js')
@endpush
