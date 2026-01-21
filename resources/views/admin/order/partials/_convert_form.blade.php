<div class="form-section">
    <div class="row g-2">
        {{-- Información de la acción --}}
        <div class="col-12">
            <div
                class="alert alert-info py-2 shadow-sm border-0 rounded-3 mb-2 d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-info-circle me-1"></i>
                    Orden <strong>#<span id="display_order_id"></span></strong>
                </span>
                <span class="badge bg-white text-info shadow-sm" id="display_customer_name">Cliente...</span>
            </div>
        </div>

        {{-- Resumen de Totales Compacto --}}
        <div class="col-12 mb-2">
            <div class="row g-2">
                <div class="col-6">
                    <div class="border rounded-3 p-2 bg-white shadow-sm border-start border-4 border-success">
                        <div class="text-muted small fw-bold uppercase" style="font-size: 0.65rem;">TOTAL EN PESOS</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold h6 mb-0">$</span>
                            <span id="display_total_ars" class="fw-bold h6 mb-0">0,00</span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded-3 p-2 bg-white shadow-sm border-start border-4 border-primary">
                        <div class="text-muted small fw-bold uppercase" style="font-size: 0.65rem;">TOTAL EN DÓLARES
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-primary fw-bold h6 mb-0">U$D</span>
                            <span id="display_total_usd" class="fw-bold h6 mb-0">0,00</span>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Desglose ultra-compacto --}}
            <div class="d-flex justify-content-center gap-3 mt-1 text-muted" style="font-size: 0.7rem;">
                <span>Puro ARS: <strong id="subtotal_ars_pure" class="text-dark">$ 0.00</strong></span>
                <span class="text-silver">|</span>
                <span>Puro USD: <strong id="subtotal_usd_pure" class="text-dark">U$D 0.00</strong></span>
            </div>
        </div>

        {{-- Entrada de datos --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 compact-select-wrapper">
                            <label class="compact-select-label fw-bold small">Método de Pago</label>
                            <x-adminlte.select name="payment_type" id="convert_payment_type" label=""
                                :options="$paymentTypes" :showPlaceholder="false" required />
                        </div>
                        <div class="col-md-6">
                            <x-bootstrap.compact-input id="convert_amount_received" name="amount_received"
                                type="number" label="Monto Recibido" placeholder="0.00" step="0.01" prefix="$"
                                required />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resultados calculados --}}
        <div class="col-12">
            <div class="card border-0 bg-light rounded-3">
                <div class="card-body py-2">
                    <div class="row g-2">
                        <div class="col-6">
                            <x-bootstrap.compact-input id="convert_change_returned" name="change_returned"
                                type="number" label="Cambio" step="0.01" prefix="$" readonly />
                        </div>
                        <div class="col-6">
                            <x-bootstrap.compact-input id="convert_remaining_balance" name="remaining_balance"
                                type="number" label="Pendiente" step="0.01" prefix="$" readonly />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="hidden_order_id" name="order_id">
        <input type="hidden" id="hidden_user_id" name="user_id" value="{{ auth()->id() }}">
        {{-- Inputs ocultos para los totales reales que enviará el formulario --}}
        <input type="hidden" id="total_amount" name="total_amount">
        <input type="hidden" id="total_amount_usd_hidden" name="total_amount_usd">
    </div>
</div>
