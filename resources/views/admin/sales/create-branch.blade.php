@extends('layouts.app')

@section('page-title', 'Crear Transferencia entre Sucursales')

@section('content')
    @php
        $currentCustomerType = 'App\Models\Branch'; // Forzamos Branch en esta vista
        $currentSaleDate = old('sale_date', $saleDate ?? now()->format('Y-m-d'));
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

    <x-adminlte.alert-manager />

    <x-adminlte.form id="saleForm" action="{{ route('web.sales.store') }}" title="Nueva Transferencia">
        <x-slot:headerActions>
            <a href="{{ route('web.sales.index') }}" class="btn btn-sm btn-default mr-1">Cancelar</a>
            <button type="submit" form="saleForm" class="btn btn-sm btn-primary">
                <i class="fas fa-save mr-1"></i>
                <span>Procesar Pago <span class="kbd-shortcut">F12</span></span>
            </button>
        </x-slot:headerActions>

        {{-- Campo oculto para definir el tipo de cliente como Sucursal --}}
        <input type="hidden" name="customer_type" value="{{ $currentCustomerType }}">

        @include('admin.sales.partials._form', [
            'order' => null,
            'branches' => $originBranch,
            'destinationBranches' => $destinationBranches,
            'statusOptions' => $statusOptions,
        ])
    </x-adminlte.form>

    {{-- @include('admin.product.partials._modal_product_search') --}}
    @include('admin.client.partials._modal-create')

    {{-- Aquí es donde ocurre la magia: pasamos isBranchTransfer --}}
    @include('admin.sales.partials._modal-payment', [
        'saleDate' => $currentSaleDate,
        'customerType' => $currentCustomerType,
        'isBranchTransfer' => true,
    ])
@endsection

@push('scripts')
    <script>
        // En sucursales usualmente no hay mapa de descuentos, pero lo definimos vacío para evitar errores JS
        window.DISCOUNT_AMOUNT_MAP = {};
    </script>
    @vite('resources/js/modules/sales/create.js')
@endpush
