@extends('layouts.app')

@section('page-title', 'Editar Pedido')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form action="{{ route('web.orders.update', $order->id) }}" id="orderForm" method="POST" title="Editar Pedido">
        @method('PUT')

        <x-slot:headerActions>
            <a href="{{ route('web.orders.index') }}" class="btn btn-sm btn-default mr-1">
                Cancelar
            </a>

            <button type="submit" form="orderForm" class="btn btn-sm btn-primary" x-bind:disabled="submitting">
                <i class="fas fa-save mr-1"></i>
                <span x-show="!submitting">Actualizar Pedido <span class="kbd-shortcut">F12</span></span>
                <span x-show="submitting" x-cloak>Actualizando...</span>
            </button>
        </x-slot:headerActions>

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
