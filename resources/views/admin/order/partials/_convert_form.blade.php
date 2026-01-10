{{-- resources/views/admin/order/partials/_convert_form.blade.php --}}

<div class="form-section">
    <div class="row g-3">
        {{-- Información de la acción --}}
        <div class="col-12">
            <div class="alert alert-info py-2 shadow-sm border-0 rounded-3 mb-1">
                <i class="fas fa-info-circle me-1"></i>
                Vas a convertir la <strong>Orden #<span id="display_order_id"></span></strong>
            </div>
        </div>

        {{-- Entrada de datos --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body py-3">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-6 compact-select-wrapper">
                            <label class="compact-select-label fw-bold small">
                                Método de Pago <span class="text-danger">*</span>
                            </label>
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
                <div class="card-body py-3">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <x-bootstrap.compact-input id="convert_change_returned" name="change_returned"
                                type="number" label="Cambio Devuelto" step="0.01" prefix="$" readonly />
                        </div>

                        <div class="col-md-6">
                            <x-bootstrap.compact-input id="convert_remaining_balance" name="remaining_balance"
                                type="number" label="Saldo Pendiente" step="0.01" prefix="$" readonly />
                        </div>

                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label class="fw-bold small">Estado del Pago</label>
                                <div id="convert_payment_status" class="mt-2">
                                    <span class="badge bg-secondary px-3 py-2">
                                        Esperando datos...
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <input type="hidden" id="hidden_order_id" name="order_id">
        <input type="hidden" id="hidden_user_id" name="user_id" value="{{ auth()->id() }}">
    </div>
</div>
