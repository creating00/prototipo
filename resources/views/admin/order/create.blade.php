@extends('layouts.app')

@section('page-title', 'Crear Pedido')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.orders.store') }}" method="POST" title="Crear Nuevo Pedido"
        submit-text="Guardar Pedido" submitting-text="Registrando pedido...">
        @include('admin.order.partials._form', [
            'order' => null,
            'branches' => $branches,
            'clients' => $clients,
            'statusOptions' => $statusOptions,
        ])
    </x-adminlte.form>

    {{-- @include('admin.product.partials._modal_product_search') --}}
    @include('admin.client.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/orders/create.js')
@endpush
