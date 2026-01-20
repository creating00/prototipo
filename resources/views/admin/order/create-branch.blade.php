@extends('layouts.app')

@section('page-title', 'Crear Pedido')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 90%;">
            <x-adminlte.alert-manager />

            <x-adminlte.form id="orderForm" action="{{ route('web.orders.store') }}" method="POST" title="Crear Nuevo Pedido">
                {{-- Header Actions --}}
                <x-slot:headerActions>
                    <a href="{{ route('web.orders.index') }}" class="btn btn-sm btn-default mr-1">
                        Cancelar
                    </a>

                    <button type="submit" form="orderForm" class="btn btn-sm btn-primary" x-bind:disabled="submitting">
                        <i class="fas fa-save mr-1"></i>
                        <span x-show="!submitting">
                            Guardar Pedido <span class="kbd-shortcut">F12</span>
                        </span>
                        <span x-show="submitting" x-cloak>
                            Registrando pedido...
                        </span>
                    </button>
                </x-slot:headerActions>

                {{-- Datos ocultos --}}
                <input type="hidden" name="customer_type" value="App\Models\Branch">
                <input type="hidden" id="existing_order_items" value="[]">

                {{-- Formulario --}}
                @include('admin.order.partials._form', [
                    'order' => null,
                    'branches' => $originBranch,
                    'destinationBranches' => $destinationBranches,
                    'statusOptions' => $statusOptions,
                ])

            </x-adminlte.form>
        </div>
    </div>

    {{-- @include('admin.product.partials._modal_product_search') --}}
    @include('admin.client.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/orders/form.js')
@endpush
