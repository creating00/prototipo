@props([
    'sale' => null,
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
    $customerType = old('customer_type') ?? ($customer_type ?? ($sale->customer_type ?? null));
    $customerId = old('customer_id', $sale->customer_id ?? null);

    $isRepair =
        (old('sale_type') ?? ($sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value)) ==
        \App\Enums\SaleType::Repair->value;
@endphp

<input type="hidden" name="user_id" value="{{ auth()->id() }}">
<input type="hidden" name="source" value="1">

{{-- Tipo de formulario dinámico --}}
@if ($customerType === 'App\Models\Client')
    @include('admin.sales.partials.sections._form_origin_client')
@else
    @include('admin.sales.partials.sections._form_origin_branch')
@endif

<hr class="my-4">

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

<h3>Productos de la Venta</h3>

<div class="table-responsive">
    <table class="table table-striped table-bsaleed align-middle" id="order-items-table">
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

<hr class="my-4">

<h3>Totales</h3>

<div id="sale-total-wrapper">
    <x-admin-lte.input-group id="total_amount" name="total_amount" label="Total del Pedido" type="number"
        step="0.01" readonly prepend="$" :value="old('total_amount', $sale->total_amount ?? 0)" />
</div>

<div id="repair-amount-wrapper" class="d-none">
    <x-admin-lte.input-group id="repair_amount" name="repair_amount" label="Costo de la Reparación" type="number"
        step="0.01" prepend="$" :value="old('repair_amount', $sale->repair_amount ?? '')" oninput="window.salePayment?.setSaleTotalFromRepair()"
        required />
</div>

<hr class="my-4">

<h3>Datos de Pago</h3>

<div class="row g-3 mb-3">
    <div class="row g-3 equal-height-selects align-items-end">
        <!-- Primera fila: Fecha, Tipo de pago, Monto recibido -->
        <div class="col-md-4">
            <x-admin-lte.input-group id="sale_date" name="sale_date" label="Fecha de Venta" type="date"
                :value="$saleDate" required />
        </div>

        <div class="col-md-4">
            <x-admin-lte.select name="payment_type" label="Tipo de Pago" :options="$paymentOptions" :value="old('payment_type', $sale->payment_type ?? 1)" required />
        </div>

        <div class="col-md-4">
            <x-admin-lte.input-group id="amount_received" name="amount_received" label="Monto Recibido" type="number"
                step="0.01" :value="old('amount_received', $sale->amount_received ?? 0)" required prepend="$"
                oninput="window.salePayment?.calculateChangeAndBalance()" />
        </div>
    </div>

    <!-- Segunda fila: Cambio, Saldo, Estado -->
    <div class="col-md-4">
        <x-admin-lte.input-group id="change_returned" name="change_returned" label="Cambio Devuelto" type="number"
            step="0.01" :value="old('change_returned', $sale->change_returned ?? 0)" readonly prepend="$" />
    </div>

    <div class="col-md-4">
        <x-admin-lte.input-group id="remaining_balance" name="remaining_balance" label="Saldo Pendiente" type="number"
            step="0.01" :value="old('remaining_balance', $sale->remaining_balance ?? 0)" readonly prepend="$" />
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Estado del Pago</label>
            <div id="payment_status_indicator" class="mt-2">
                <span class="badge bg-secondary">Esperando datos...</span>
            </div>
        </div>
    </div>

    <!-- Tercera fila: Notas (ancho completo) -->
    <div class="col-md-12" hidden>
        <x-admin-lte.textarea id="notes_payment" name="payment_notes" label="Notas de Pago (opcional)" rows="3">
            {{ old('payment_notes', $sale->payment_notes ?? '') }}
        </x-admin-lte.textarea>
    </div>
</div>

<div class="form-group mt-3">
    <label for="notes">Notas u Observaciones</label>
    <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
        placeholder="Agregar comentarios adicionales...">{{ old('notes', $sale->notes ?? '') }}</textarea>

    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
