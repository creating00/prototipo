@props([
    'order' => null,
    'branches' => [],
    'clients' => [],
    'statusOptions' => [],
])

@push('styles')
    <style>
        .equal-height-selects .form-group {
            height: 100%;
        }

        .equal-height-selects .select2-container {
            height: 100%;
        }
    </style>
@endpush

@php
    $customerType = old('customer_type') ?? ($customer_type ?? ($order->customer_type ?? null));
    $customerId = old('customer_id', $order->customer_id ?? null);
@endphp

<input type="hidden" name="user_id" value="{{ auth()->id() }}">
<input type="hidden" name="source" value="1">

{{-- Tipo de formulario dinámico --}}
@if ($customerType === 'App\Models\Client')
    @include('admin.order.partials.sections._form_origin_client')
@else
    @include('admin.order.partials.sections._form_origin_branch')
@endif

<hr class="my-3">

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label for="product_search_code" class="form-label">Agregar Producto</label>

        <div class="input-group">
            <input type="text" id="product_search_code" class="form-control" placeholder="Código de producto (SKU)"
                autocomplete="off">

            <button type="button" class="btn btn-custom btn-custom-aqua" id="btn-open-product-modal">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>

<h3>Productos del Pedido</h3>

<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle" id="order-items-table">
        <thead>
            <tr>
                <th width="35%">Producto</th>
                <th width="12%">Stock</th>
                <th width="12%">Precio</th>
                <th width="12%">Cantidad</th>
                <th width="15%">Subtotal</th>
                <th width="8%"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<hr class="my-3">

<h3>Totales</h3>

<x-admin-lte.input-group id="total_amount" name="total_amount" label="Total del Pedido" type="number" step="0.01"
    readonly prepend="$" :value="old('total_amount', $order->total_amount ?? 0)" />


<div class="form-group mt-3">
    <label for="notes">Notas u Observaciones</label>
    <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
        placeholder="Agregar comentarios adicionales...">{{ old('notes', $order->notes ?? '') }}</textarea>

    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
