@props([
    'sale' => null,
    'branches' => [],
    'clients' => [],
    'statusOptions' => [],
])

@push('styles')
    @vite('resources/css/modules/sales/sales-styles.css')
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
<input type="hidden" name="customer_type" value="{{ $customerType }}">

<div id="hidden-sync-fields">
    <input type="hidden" name="sale_date" id="hidden_sale_date" value="{{ $saleDate }}">
    <input type="hidden" name="payment_type" id="hidden_payment_type" value="{{ old('payment_type', 1) }}">
    <input type="hidden" name="amount_received" id="hidden_amount_received" value="{{ old('amount_received', 0) }}">
    <input type="hidden" name="change_returned" id="hidden_change_returned" value="0">
    <input type="hidden" name="remaining_balance" id="hidden_remaining_balance" value="0">
    <input type="hidden" name="repair_amount" id="hidden_repair_amount" value="">
    <input type="hidden" name="discount_id" id="hidden_discount_id" value="{{ old('discount_id', '') }}">
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
                @include('admin.sales.partials.sections._origin')
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-md-9">
        {{-- Productos de la Venta --}}
        <div class="card card-outline card-success mb-3">
            <div class="card-header">
                {{-- Cambiamos align-items-end por align-items-center --}}
                <div class="row align-items-center w-100 g-3">
                    <div class="col-md-6 d-flex align-items-center">
                        {{-- Eliminamos margin bottom para que no desplace el eje --}}
                        <h3 class="card-title mb-0 mt-2">Productos / Items</h3>
                    </div>
                    <div class="col-md-6">
                        <div class="compact-input-wrapper">
                            <label class="compact-input-label">
                                Buscador de Productos <kbd class="kbd-shortcut">F1</kbd>
                            </label>

                            <div class="input-group input-group-sm">
                                <input type="text" id="product_search_code" class="form-control compact-input"
                                    placeholder="Escanee SKU o escriba código..." autocomplete="off">

                                <button type="button" class="btn btn-custom btn-custom-aqua"
                                    id="btn-open-product-modal">
                                    <i class="fas fa-list-ul mr-1"></i>
                                    <span class="kbd-shortcut"
                                        style="color: inherit; background: rgba(0,0,0,0.1); border: none; box-shadow: none;">
                                        F4
                                    </span>
                                </button>
                            </div>
                        </div>
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
