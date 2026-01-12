@extends('layouts.app')

@section('page-title', 'Editar Venta')

@section('content')
    @php
        $customerType = old('customer_type', $customerType ?? $sale->customer_type);
        $currentSaleDate = old(
            'sale_date',
            isset($sale) && $sale->sale_date
                ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d')
                : $saleDate ?? now()->format('Y-m-d'),
        );
    @endphp

    <x-adminlte.alert-manager />

    <x-adminlte.form id="saleForm" action="{{ route('web.sales.update', $sale->id) }}" method="POST" title="Editar Pedido"
        submit-text="Actualizar Pedido" submitting-text="Actualizando pedido...">
        @method('PUT')

        <input type="hidden" id="existing_order_items" value='@json($existingOrderItems)'>

        <x-slot:headerActions>
            <a href="{{ route('web.sales.index') }}" class="btn btn-sm btn-default mr-1">Cancelar</a>
            {{-- Este botón busca un formulario con id="saleForm" --}}
            <button type="submit" form="saleForm" class="btn btn-sm btn-primary" x-bind:disabled="submitting">
                <i class="fas fa-save mr-1"></i>
                <span x-show="!submitting">Procesar Pago <span class="kbd-shortcut">F12</span></span>
                <span x-show="submitting" x-cloak>Procesando...</span>
            </button>
        </x-slot:headerActions>

        {{-- Contenido del Formulario --}}
        @include('admin.sales.partials._form')

    </x-adminlte.form>

    {{-- @include('admin.product.partials._modal_product_search') --}}
    @include('admin.client.partials._modal-create')
    @include('admin.sales.partials._modal-payment', [
        'saleDate' => $currentSaleDate,
        'customerType' => $customerType,
    ])

@endsection

@push('scripts')
    <script>
        window.DISCOUNT_AMOUNT_MAP = @json($discountMap);
    </script>

    @vite('resources/js/modules/sales/edit.js')
@endpush
