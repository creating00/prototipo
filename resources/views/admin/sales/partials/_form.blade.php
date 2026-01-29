@props([
    'sale' => null,
    'branches' => [],
    'clients' => [],
    'statusOptions' => [],
])

@push('styles')
    @vite('resources/css/modules/sales/sales-styles.css')
@endpush

{{-- @php
    $customerType = old('customer_type', $customer_type ?? $sale?->customer_type);
    $customerId = old('customer_id', $sale->customer_id ?? null);
    $currentSaleDate = old('sale_date', $sale->sale_date ?? ($saleDate ?? now()->format('Y-m-d')));

    $isRepair =
        (old('sale_type') ?? ($sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value)) ==
        \App\Enums\SaleType::Repair->value;
@endphp --}}

<input type="hidden" name="user_id" value="{{ auth()->id() }}">
<input type="hidden" name="source" value="1">
<input type="hidden" name="customer_type" value="{{ $customerType }}">

<div id="hidden-sync-fields">
    <input type="hidden" name="sale_date" id="hidden_sale_date" value="{{ $saleDate }}">
    <input type="hidden" name="enable_dual_payment" id="hidden_enable_dual_payment" value="{{ $isDual ? 1 : 0 }}">

    <input type="hidden" name="payment_type" id="hidden_payment_type"
        value="{{ old('payment_type', $pago1->payment_type ?? 1) }}">
    <input type="hidden" name="amount_received" id="hidden_amount_received" value="{{ old('amount_received', 0) }}">

    <input type="hidden" name="payment_type_2" id="hidden_payment_type_2"
        value="{{ old('payment_type_2', $pago2->payment_type ?? '') }}">
    <input type="hidden" name="amount_received_2" id="hidden_amount_received_2"
        value="{{ old('amount_received_2', $pago2->amount ?? 0) }}">

    <input type="hidden" name="change_returned" id="hidden_change_returned" value="0">
    <input type="hidden" name="remaining_balance" id="hidden_remaining_balance" value="0">
    <input type="hidden" name="repair_amount" id="hidden_repair_amount" value="">
    <input type="hidden" name="discount_id" id="hidden_discount_id" value="{{ old('discount_id', '') }}">
    <input type="hidden" name="discount_amount" id="hidden_discount_amount" value="{{ old('discount_amount', 0) }}">
    <input type="hidden" name="subtotal_amount" id="subtotal_amount"
        value="{{ old('subtotal_amount', $sale->subtotal_amount ?? 0) }}">
    <input type="hidden" id="totals_source" value='{}'>
    <input type="hidden" name="totals" id="hidden_totals" value="{{ old('totals', json_encode([])) }}">

    <input type="hidden" name="payment_method_id" id="hidden_payment_method_id"
        value="{{ old('payment_method_id', $pago1->payment_method_id ?? '') }}">
    <input type="hidden" name="payment_method_type" id="hidden_payment_method_type"
        value="{{ old('payment_method_type', $pago1->payment_method_type ?? '') }}">

    <input type="hidden" name="payment_method_id_2" id="hidden_payment_method_id_2"
        value="{{ old('payment_method_id_2', $pago2->payment_method_id ?? '') }}">
    <input type="hidden" name="payment_method_type_2" id="hidden_payment_method_type_2"
        value="{{ old('payment_method_type_2', $pago2->payment_method_type ?? '') }}">
</div>

<div class="row">
    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-md-3">
        {{-- Origen de la Venta --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Origen de la Venta</h3>
                <div class="card-tools">
                    <span class="kbd-shortcut" title="Atajo para nuevo cliente (F2)">F2</span>
                </div>
            </div>
            <div class="card-body">
                @if ($customerType === 'App\Models\Client')
                    @include('admin.sales.partials.sections._form_origin_client')
                @elseif ($customerType === 'App\Models\Branch')
                    @include('admin.sales.partials.sections._form_origin_branch')
                @endif
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-md-9">
        {{-- Productos de la Venta --}}
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                <div class="row align-items-center w-100 g-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <h3 class="card-title mb-0 mt-2">Productos / Items</h3>
                    </div>
                    <div class="col-md-6">
                        @include('admin.sales.partials.sections._product_search')
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                @include('admin.sales.partials.sections._products')
            </div>
        </div>

        {{-- Totales y Pago --}}
        <div class="card card-outline card-info sticky-summary">
            <div class="card-header">
                <h3 class="card-title">Totales y Pago</h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                        data-bs-target="#modalSalePayment" data-focus-target="#amount_received">
                        <i class="fas fa-edit"></i> Editar
                        <span class="kbd-shortcut">F10</span>
                    </button>
                </div>
            </div>

            <div class="card-body">
                @include('admin.sales.partials.sections._summary')
            </div>
        </div>
    </div>
</div>
