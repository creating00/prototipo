@props([
    'order' => null,
    'branches' => [],
    'clients' => [],
    'statusOptions' => [],
])

@push('styles')
    @vite('resources/css/modules/sales/sales-styles.css')
    <style>
        .total-field-footer {
            background-color: #f8f9fa !important;
            border: 1px solid #ced4da !important;
            font-size: 1.25rem;
            font-weight: bold;
        }

        /* Ajuste para que el input group en BS5 se vea como el de AdminLTE */
        .card-footer .input-group-text {
            font-weight: bold;
        }
    </style>
@endpush

@php
    $customerType = old('customer_type', $customer_type ?? ($order->customer_type ?? 'App\Models\Client'));
    $customerId = old('customer_id', $order->customer_id ?? null);
@endphp

{{-- Campos ocultos base --}}
<input type="hidden" name="user_id" value="{{ auth()->id() }}">
<input type="hidden" name="source" value="1">
<input type="hidden" name="customer_type" value="{{ $customerType }}">

<div class="row g-4">
    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-md-3">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Origen del Pedido</h3>
            </div>
            <div class="card-body">
                @if ($customerType === 'App\Models\Client')
                    @include('admin.order.partials.sections._form_origin_client')
                @else
                    @include('admin.order.partials.sections._form_origin_branch')
                @endif
            </div>
        </div>

        <div class="card card-outline card-secondary mt-3 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Observaciones</h3>
            </div>
            <div class="card-body">
                <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                    placeholder="Comentarios adicionales...">{{ old('notes', $order->notes ?? '') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-md-9">
        <div class="card card-outline card-success mb-3 shadow-sm">
            <div class="card-header">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <h3 class="card-title mb-0">Productos del Pedido</h3>
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
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover align-middle mb-0" id="order-items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%">Producto</th>
                                <th style="width: 12%">Stock</th>
                                <th style="width: 12%">Precio</th>
                                <th style="width: 12%">Cantidad</th>
                                <th style="width: 15%">Subtotal</th>
                                <th style="width: 8%" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FOOTER CON SINTAXIS BOOTSTRAP 5 --}}
            <div class="card-footer bg-white border-top py-3">
                <div class="row align-items-center">
                    <div class="col-md-7 text-muted d-none d-md-block">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2 text-info"></i>
                            <span class="small">Los totales se actualizan automáticamente al modificar
                                cantidades.</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-success text-white border-success">TOTAL</span>
                            <input type="number" name="total_amount" id="total_amount"
                                class="form-control text-end total-field-footer"
                                value="{{ old('total_amount', $order->total_amount ?? 0) }}" readonly step="0.01">
                            <span class="input-group-text bg-light">$</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
