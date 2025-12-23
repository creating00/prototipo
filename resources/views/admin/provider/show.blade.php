@extends('layouts.app')

@section('page-title', 'Detalle del Proveedor')

@include('admin.provider.partials.provider-product._modal-create', [
    'provider' => $provider,
    'products' => $products,
])

@include('admin.provider.partials.provider-product._modal-edit')
@include('admin.provider.partials.provider-product._prices')

@section('content')

    <div class="container-fluid">

        <x-admin-lte.alert-manager />

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    {{ $provider->business_name }}
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <strong>CUIT</strong><br>
                        {{ $provider->tax_id }}
                    </div>

                    <div class="col-md-4">
                        <strong>Contacto</strong><br>
                        {{ $provider->contact_name ?? '—' }}
                    </div>

                    <div class="col-md-4">
                        <strong>Teléfono</strong><br>
                        {{ $provider->phone ?? '—' }}
                    </div>

                    <div class="col-md-12">
                        <strong>Dirección</strong><br>
                        {{ $provider->address ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        <x-admin-lte.data-table tableId="provider-products-table" title="Productos asociados" :headers="$headers"
            :rowData="$rowData" :hiddenFields="$hiddenFields" withActions="true">
            {{-- Acciones por fila --}}
            <x-admin-lte.button color="custom-teal" size="sm" icon="fas fa-edit" class="me-1 btn-edit" />

            {{-- <x-admin-lte.button color="custom-emerald" size="sm" icon="fas fa-dollar-sign" class="btn-price" /> --}}

            {{-- Botones del header --}}
            <x-slot name="headerButtons">
                <x-admin-lte.button color="primary" icon="fas fa-plus" class="me-1 btn-header-attach-product"
                    data-bs-toggle="modal" data-bs-target="#attachProductModal">
                    Asociar producto
                </x-admin-lte.button>

            </x-slot>
        </x-admin-lte.data-table>

    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/providers/show.js')
@endpush
