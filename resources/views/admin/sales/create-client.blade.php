@extends('layouts.app')

@section('page-title', 'Crear Venta')

@section('content')
    @php
        $customerType = old('customer_type', $customer_type ?? $sale?->customer_type);
        $customerId = old('customer_id', $sale->customer_id ?? null);
        $currentSaleDate = old('sale_date', $sale->sale_date ?? ($saleDate ?? now()->format('Y-m-d')));

        $pago1 = null;
        $pago2 = null;
        $isDual = false;
        $isRepair = false;
    @endphp

    @if (session('print_receipt'))
        <script>
            (() => {
                const data = @json(session('print_receipt'));

                const url = data.type === 'a4' ?
                    "{{ route('sales.a4', ':id') }}" :
                    "{{ route('sales.ticket', ':id') }}";

                window.open(url.replace(':id', data.sale_id), '_blank');
            })();
        </script>
    @endif

    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 100%;">
            <x-adminlte.alert-manager />

            <x-adminlte.form id="saleForm" action="{{ route('web.sales.store') }}" title="Nueva Venta">
                <x-slot:headerActions>
                    <a href="{{ route('web.sales.index') }}" class="btn btn-sm btn-default mr-1">Cancelar</a>
                    <button type="submit" form="saleForm" class="btn btn-sm btn-primary" x-bind:disabled="submitting">
                        <i class="fas fa-save mr-1"></i>
                        <span x-show="!submitting">Procesar Pago <span class="kbd-shortcut">F12</span></span>
                        <span x-show="submitting" x-cloak>Procesando...</span>
                    </button>
                </x-slot:headerActions>

                {{-- Contenido del Formulario --}}
                @include('admin.sales.partials._form')
            </x-adminlte.form>
        </div>
    </div>

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
        //console.log(window.DISCOUNT_AMOUNT_MAP);
    </script>
    <script>
        window.repairAmountsMap = @json($repairAmountsMap);
    </script>

    @vite('resources/js/modules/sales/create.js')
@endpush
