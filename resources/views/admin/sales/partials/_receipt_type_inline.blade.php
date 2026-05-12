@php
    $layout = $layout ?? 'full'; // 'full' o 'compact'
@endphp

<div class="{{ $layout === 'full' ? 'compact-select-wrapper mt-2' : '' }}">
    @if ($layout === 'full')
        <label class="compact-select-label">Tipo de Comprobante</label>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-3 {{ $layout === 'full' ? 'mt-3' : 'mt-1' }}">
        {{-- Radio: No imprimir --}}
        {{-- <div class="form-check d-flex align-items-center m-0">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_none" value=""
                {{ old('receipt_type', $default ?? '') === '' ? 'checked' : '' }}
                style="width: 1rem; height: 1rem; cursor: pointer;">
            <label class="form-check-label ms-1 small text-muted" for="receipt_none" style="cursor: pointer;">
                Sin impresión
            </label>
        </div> --}}

        {{-- Radio: Ticket --}}
        {{-- <div class="form-check d-flex align-items-center m-0">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_ticket" value="ticket"
                {{ old('receipt_type', $default ?? '') === 'ticket' ? 'checked' : '' }}
                style="width: 1rem; height: 1rem; cursor: pointer;">
            <label class="form-check-label ms-1 small" for="receipt_ticket" style="cursor: pointer;">
                Ticket
            </label>
        </div> --}}

        {{-- Radio: A4 --}}
        {{-- <div class="form-check d-flex align-items-center m-0">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_a4" value="a4"
                {{ old('receipt_type', $default ?? '') === 'a4' ? 'checked' : '' }}
                style="width: 1rem; height: 1rem; cursor: pointer;">
            <label class="form-check-label ms-1 small" for="receipt_a4" style="cursor: pointer;">
                A4
            </label>
        </div> --}}

        {{-- Checkbox: Factura (solo si es full o si lo necesitas) --}}
        <div class="form-check ms-auto">
            <input class="form-check-input" type="checkbox" name="requires_invoice" id="requires_invoice" value="1"
                {{ old('requires_invoice', $sale->requires_invoice ?? false) ? 'checked' : '' }}>
            <label class="form-check-label small fw-bold" for="requires_invoice">
                Factura
            </label>
        </div>
    </div>
</div>
