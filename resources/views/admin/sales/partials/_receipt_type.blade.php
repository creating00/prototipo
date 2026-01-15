{{-- Tipo de comprobante --}}
<div class="compact-select-wrapper mt-2">
    <label class="compact-select-label">
        Tipo de Comprobante
    </label>

    <div class="d-flex flex-nowrap align-items-start gap-1 mt-3">
        <div class="form-check flex-shrink-0 d-flex align-items-center" style="font-size: 0.7rem; height: 2rem;">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_none" value=""
                {{ old('receipt_type', $default ?? '') === '' ? 'checked' : '' }}
                style="width: 1.1rem; height: 1.1rem; margin-top: 0;"> {{-- Tamaño Bootstrap normal --}}
            <label class="form-check-label ms-1" for="receipt_none"
                style="font-weight: normal; padding-right: 0.3rem; line-height: 0.9;">
                No<br>imprimir
            </label>
        </div>
        <div class="form-check flex-shrink-0 d-flex align-items-center" style="font-size: 0.7rem; height: 2rem;">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_ticket" value="ticket"
                {{ old('receipt_type', $default ?? '') === 'ticket' ? 'checked' : '' }}
                style="width: 1.1rem; height: 1.1rem; margin-top: 0;"> {{-- Tamaño Bootstrap normal --}}
            <label class="form-check-label ms-1" for="receipt_ticket"
                style="font-weight: normal; padding-right: 0.3rem;">
                Ticket
            </label>
        </div>
        <div class="form-check flex-shrink-0 d-flex align-items-center" style="font-size: 0.7rem; height: 2rem;">
            <input class="form-check-input" type="radio" name="receipt_type" id="receipt_a4" value="a4"
                {{ old('receipt_type', $default ?? '') === 'a4' ? 'checked' : '' }}
                style="width: 1.1rem; height: 1.1rem; margin-top: 0;"> {{-- Tamaño Bootstrap normal --}}
            <label class="form-check-label ms-1" for="receipt_a4" style="font-weight: normal;">
                A4
            </label>
        </div>
    </div>
</div>