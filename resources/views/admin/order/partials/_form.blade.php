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
                <div class="row align-items-center w-100 g-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <h3 class="card-title mb-0 mt-2">Productos / Items</h3>
                    </div>
                    <div class="col-md-6">
                        @include('admin.sales.partials.sections._product_search')
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bsaleed align-middle" id="order-items-table">
                    <thead>
                        <tr>
                            <th width="18%">Producto</th>
                            <th width="7%">Stock</th>
                            <th width="20%">Precio</th>
                            <th width="6%">Cantidad</th>
                            <th width="15%">Subtotal</th>
                            <th width="8%"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            {{-- FOOTER CON SINTAXIS BOOTSTRAP 5 --}}
            <div class="card-footer bg-white border-top py-3">
                <div class="row align-items-center">
                    <div class="col-md-6 d-none d-md-block">
                        <div class="d-flex flex-column justify-content-center h-100">
                            <label class="small text-uppercase fw-bold text-primary mb-1">
                                <i class="fas fa-chart-line me-1"></i> Cotización Dólar Blue (Venta)
                            </label>

                            <div class="input-group shadow-sm w-75">
                                <span class="input-group-text bg-primary text-white border-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <input type="text" id="current_dollar_price"
                                    class="form-control bg-white text-center fw-bold" readonly value="Cargando...">
                                <span class="input-group-text bg-light text-muted small">ARS</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="input-group shadow-sm mb-2">
                            <span class="input-group-text bg-success text-white border-success">TOTAL ARS</span>
                            <input type="number" name="total_amount" id="total_amount"
                                class="form-control text-end total-field-footer"
                                value="{{ old('total_amount', $order->total_amount ?? 0) }}" readonly step="0.01">
                            <span class="input-group-text bg-light">$</span>
                        </div>

                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-info text-white border-info">TOTAL USD</span>
                            <input type="text" id="total_amount_usd" class="form-control text-end bg-light"
                                value="0.00" readonly>
                            <span class="input-group-text bg-light">U$D</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
