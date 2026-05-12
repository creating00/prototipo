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

{{-- Total + Estado + Notas (una línea) --}}
<div class="row g-2 align-items-center">
    {{-- Total --}}
    <div class="col-auto d-flex flex-column me-4">
        <small class="text-muted">Total</small>
        <div class="fs-5 fw-bold text-success lh-1">
            $ <span id="summary_total">0.00</span>
        </div>
    </div>

    {{-- Estado del Pago --}}
    <div class="col-auto me-4">
        <small class="text-muted d-block">Estado del Pago</small>
        <div id="summary_payment_status">
            <span class="badge bg-secondary">Pendiente</span>
        </div>
    </div>

    {{-- Notas (ocupa el resto del espacio) --}}
    <div class="col">
        <x-bootstrap.compact-text-area id="notes" name="notes" label="Notas" rows="2"
            placeholder="Observaciones..." value="{{ old('notes', $sale->notes ?? '') }}" />
    </div>
</div>