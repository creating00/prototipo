{{-- resources/views/admin/order/partials/_convert_form.blade.php --}}

<div class="form-section">
    <div class="row g-3">
        {{-- Info visual para el usuario --}}
        <div class="col-md-12 mb-2">
            <div class="alert alert-info py-2 shadow-sm border-0 rounded-3">
                <i class="fas fa-info-circle me-1"></i>
                Vas a convertir la <strong>Orden #<span id="display_order_id"></span></strong>
            </div>
        </div>

        {{-- Tipo de Pago usando componente Select --}}
        <div class="col-md-12 mb-3">
            <x-adminlte.select name="payment_type" id="convert_payment_type" label="Método de Pago" :options="$paymentTypes"
                required />
        </div>

        {{-- Monto Recibido usando componente Compact Input --}}
        <div class="col-md-12 mb-3">
            <x-bootstrap.compact-input id="convert_amount_received" name="amount_received" type="number"
                label="Monto Recibido" placeholder="0.00" step="0.01" required />
        </div>

        {{-- Campo oculto para asegurar que el ID viaje en el FormData si fuera necesario --}}
        <input type="hidden" id="hidden_order_id" name="order_id">
    </div>
</div>
