<div class="col-md-12" hidden> <x-bootstrap.compact-text-area id="notes_payment" name="payment_notes"
        label="Notas de Pago (opcional)" rows="3" value="{{ old('payment_notes', $sale->payment_notes ?? '') }}" />
</div>
<x-bootstrap.compact-text-area id="notes" name="notes" label="Notas u Observaciones" rows="4"
    placeholder="Agregar comentarios adicionales..." value="{{ old('notes', $sale->notes ?? '') }}" />
