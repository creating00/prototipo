@extends('layouts.app')

@section('page-title', 'Crear Venta')

@section('content')
    <x-adminlte.alert-manager />

    <x-adminlte.form id="saleForm" action="{{ route('web.sales.store') }}" title="Nueva Venta">

        <x-slot:headerActions>
            <a href="{{ route('web.sales.index') }}" class="btn btn-sm btn-default mr-1">Cancelar</a>
            <button type="submit" form="saleForm" class="btn btn-sm btn-primary" x-bind:disabled="submitting">
                <i class="fas fa-save mr-1"></i>
                <span x-show="!submitting">Procesar Pago <span class="kbd-shortcut">F10</span></span>
                <span x-show="submitting" x-cloak>Procesando...</span>
            </button>
        </x-slot:headerActions>

        {{-- Contenido del Formulario --}}
        @include('admin.sales.partials._form')

    </x-adminlte.form>

    @include('admin.product.partials._modal_product_search')
    @include('admin.client.partials._modal-create')
    @include('admin.sales.partials._modal-payment')
@endsection

@push('scripts')
    <script>
        window.DISCOUNT_AMOUNT_MAP = @json($discountMap);
        console.log(window.DISCOUNT_AMOUNT_MAP);
    </script>

    @vite('resources/js/modules/sales/create.js')
@endpush
