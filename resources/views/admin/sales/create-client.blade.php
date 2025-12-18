@extends('layouts.app')

@section('page-title', 'Crear Venta')

@section('content')
    <x-admin-lte.alert-manager />

    <x-admin-lte.form action="{{ route('web.sales.store') }}" method="POST" title="Crear Nuevo Venta"
        submit-text="Guardar Venta" submitting-text="Registrando pedido...">
        <input type="hidden" name="customer_type" value="App\Models\Client">

        @include('admin.sales.partials._form', [
            'order' => null,
            'branches' => $branches,
            'clients' => $clients,
            'statusOptions' => $statusOptions,
        ])
    </x-admin-lte.form>

    @include('admin.product.partials._modal_product_search')
    @include('admin.client.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/sales/create.js')
@endpush
