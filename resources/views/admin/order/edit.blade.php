@extends('layouts.app')

@section('page-title', 'Editar Pedido')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.orders.update', $order->id) }}" method="POST" title="Editar Pedido"
        submit-text="Actualizar Pedido" submitting-text="Actualizando pedido...">
        @method('PUT')
        <input type="hidden" id="existing_order_items" value='@json($existingOrderItems)'>

        @include('admin.order.partials._form', [
            'order' => $order,
            'branches' => $branches,
            'clients' => $clients,
            'statusOptions' => $statusOptions,
        ])
    </x-adminlte.form>

    @include('admin.product.partials._modal_product_search')
    @include('admin.client.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/orders/form.js')
@endpush
