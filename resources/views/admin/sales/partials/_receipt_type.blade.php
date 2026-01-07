{{-- Tipo de comprobante --}}
<div class="compact-select-wrapper mt-2">
    <label class="compact-select-label">
        Tipo de Comprobante
    </label>

    <div class="d-flex gap-3 mt-3">
        <div class="form-check form-check-sm">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_none" value=""
                {{ old('receipt_type', $default ?? '') === '' ? 'checked' : '' }}>
            <label class="form-check-label small" for="receipt_none">
                No imprimir
            </label>
        </div>

        <div class="form-check form-check-sm">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_ticket" value="ticket"
                {{ old('receipt_type', $default ?? '') === 'ticket' ? 'checked' : '' }}>
            <label class="form-check-label small" for="receipt_ticket">
                Ticket
            </label>
        </div>

        <div class="form-check form-check-sm">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_a4" value="a4"
                {{ old('receipt_type', $default ?? '') === 'a4' ? 'checked' : '' }}>
            <label class="form-check-label small" for="receipt_a4">
                A4
            </label>
        </div>
    </div>
</div>
