{{-- Subtotal / Descuento --}}
<div class="row g-2 mb-2">
    <div class="col-6">
        <small class="text-muted">Subtotal</small>
        <div class="fw-bold">
            $ <span id="summary_subtotal">0.00</span>
        </div>
    </div>

    <div class="col-6 text-end">
        <small class="text-muted">Descuento</small>
        <div class="fw-bold text-danger">
            - $ <span id="summary_discount">0.00</span>
        </div>
    </div>
</div>

<hr class="my-2">

{{-- Total + Estado + Notas --}}
<div class="row g-2 align-items-stretch">

    {{-- Total y Estado --}}
    <div class="col-8 d-flex flex-column justify-content-between">
        <div>
            <small class="text-muted">Total</small>
            <div class="fs-3 fw-bold text-success lh-1">
                $ <span id="summary_total">0.00</span>
            </div>
        </div>

        <div class="mt-2">
            <small class="text-muted">Estado del Pago</small>
            <div id="summary_payment_status">
                <span class="badge bg-secondary">Pendiente</span>
            </div>
        </div>
    </div>

    {{-- Notas --}}
    <div class="col-4">
        <x-bootstrap.compact-text-area id="notes" name="notes" label="Notas" rows="5"
            placeholder="Observaciones..." value="{{ old('notes', $sale->notes ?? '') }}" />
    </div>
</div>
