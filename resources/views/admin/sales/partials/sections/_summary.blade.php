{{-- RESUMEN DE VENTA --}}
<div class="row g-3 align-items-start">

    {{-- COLUMNA IZQUIERDA --}}
    <div class="col-md-6">

        <div class="mb-1">
            <x-bootstrap.compact-input id="sale_date" name="sale_date_visible" type="date" label="Fecha"
                value="{{ $saleDate }}" />
        </div>

        <div class="mb-1">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">
                    Tipo de Pago
                </label>
                <x-adminlte.select name="payment_type_visible" label="" :options="$paymentOptions" :value="old('payment_type', $sale->payment_type ?? 1)"
                    :showPlaceholder="false" />
            </div>
        </div>

        <div>
            <x-bootstrap.compact-input id="amount_received" name="amount_received_visible" type="number"
                label="Monto Recibido" step="0.01" prefix="$"
                value="{{ old('amount_received', $sale->amount_received ?? '') }}" />
        </div>

    </div>

    {{-- COLUMNA DERECHA --}}
    <div class="col-md-6">

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Subtotal</small>
            <span class="fw-semibold">$ <span id="summary_subtotal">0.00</span></span>
        </div>

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Descuento</small>
            <span class="fw-semibold text-danger">
                - $ <span id="summary_discount">0.00</span>
            </span>
        </div>

        <hr class="my-2">

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Total</small>
            <span class="fw-bold text-success fs-6">
                $ <span id="summary_total">0.00</span>
            </span>
        </div>

        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Saldo Pendiente</small>
            <span class="fw-semibold text-warning">
                $ <span id="summary_remaining">0.00</span>
            </span>
        </div>

        <div class="d-flex justify-content-between">
            <small class="text-muted">Cambio</small>
            <span class="fw-semibold text-info">
                $ <span id="summary_change">0.00</span>
            </span>
        </div>

    </div>
</div>

<hr class="my-3">

{{-- Notas --}}
<div class="row">
    <div class="col">
        <x-bootstrap.compact-text-area id="notes" name="notes" label="Notas" rows="2"
            placeholder="Observaciones..." value="{{ old('notes', $sale->notes ?? '') }}" />
    </div>
</div>
