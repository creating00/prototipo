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

        {{-- Checkbox: Cobrar en dólares --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body py-2 px-3">
                    <x-adminlte.checkbox name="pay_in_dollars" id="pay_in_dollars" label="Cobrar en dólares"
                        value="1" />
                </div>
            </div>
        </div>

        {{-- Cotización USD Blue (se muestra al activar checkbox) --}}
        <div class="col-12 d-none" id="exchange_rate_section">
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded border">
                        <small class="text-muted fw-bold text-uppercase">Cotización USD Blue</small>
                        <div class="d-flex align-items-center">
                            <span class="me-2 fw-bold text-primary">$</span>
                            <input type="number" id="exchange_rate_blue" name="exchange_rate_blue"
                                class="form-control form-control-sm text-end fw-bold" style="width: 100px;"
                                value="1000" step="1" readonly>
                        </div>
                    </div>
                </div>
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

            {{-- Equivalente USD (solo visible cuando se cobra en dólares) --}}
            <div class="d-flex justify-content-between mb-1 border-top pt-2 mt-2" id="wrapper_usd_total"
                style="display: none;">
                <small class="text-muted text-uppercase fw-bold">Equivalente USD</small>
                <span class="fw-bold text-primary">
                    U$D <span id="summary_total_usd">0.00</span>
                </span>
            </div>
        </div>

        {{-- Switch para habilitar doble método de pago --}}
        <div class="card border-0 shadow-sm rounded-3 mb-2">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    {{-- Switch Dual --}}
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enable_dual_payment">
                        <label class="form-check-label fw-bold" for="enable_dual_payment">
                            Pago Dual / Editar Montos
                        </label>
                    </div>

                    {{-- Separador vertical opcional en desktop --}}
                    <div class="border-start mx-3 d-none d-md-block" style="height: 30px;"></div>

                    {{-- El Partial en modo compacto --}}
                    @include('admin.sales.partials._receipt_type_inline', [
                        'default' => 'ticket',
                        'layout' => 'compact',
                    ])
                </div>
            </div>
        </div>

        {{-- Entrada de datos - Método de Pago Simple (por defecto) --}}
        <div class="col-12" id="single_payment_section">
            <div class="card border-0 shadow-sm rounded-3 mb-2">
                <div class="card-body py-3">
                    {{-- Fila 1: Método + Banco / Cuenta --}}
                    <div class="row g-3 align-items-end">
                        {{-- Método de Pago --}}
                        <div class="col-md-6 compact-select-wrapper">
                            <label class="compact-select-label fw-bold small">Método de Pago</label>
                            <x-adminlte.select name="payment_type" id="convert_payment_type" :options="$paymentTypes"
                                :showPlaceholder="false" required />
                        </div>

                        {{-- Banco (Card) --}}
                        <div class="col-md-6 d-none" id="container_bank_id_single">
                            <div class="compact-select-wrapper">
                                <label class="compact-select-label">Banco</label>
                                <x-adminlte.select name="bank_id" id="bank_id_single" :options="$banks" />
                            </div>
                        </div>

                        {{-- Cuenta (Transfer) --}}
                        <div class="col-md-6 d-none" id="container_bank_account_id_single">
                            <div class="compact-select-wrapper">
                                <label class="compact-select-label">Cuenta</label>
                                <x-adminlte.select name="bank_account_id" id="bank_account_id_single"
                                    :options="$bankAccounts" />
                            </div>
                        </div>
                    </div>

                    {{-- Fila 2: Monto --}}
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <x-bootstrap.compact-input id="convert_amount_received" name="amount_received"
                                type="number" label="Monto Recibido" placeholder="0.00" step="0.01" prefix="$"
                                required />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Entrada de datos - Doble Método de Pago (se muestra al activar switch) --}}
        <div class="col-12 d-none" id="dual_payment_section">
            <div class="row g-2">
                {{-- Bloque Pago 1 --}}
                <div class="col-md-6">
                    <div class="card card-outline card-secondary shadow-sm border bg-light">
                        <div class="card-body p-3">
                            <label class="small text-muted mb-2 d-block text-uppercase fw-bold">
                                <i class="fas fa-circle-1 me-1"></i> Método 1
                            </label>

                            {{-- Tipo de pago 1 --}}
                            <div class="mb-2">
                                <x-adminlte.select name="payment_type_1" id="payment_type_1" label=""
                                    :options="$paymentTypes" :showPlaceholder="false" />
                            </div>

                            {{-- Banco Pago 1 (Polimórfico: Bank) --}}
                            <div class="mb-2 d-none" id="container_bank_id_1">
                                <div class="compact-select-wrapper">
                                    <label class="compact-select-label">Banco</label>
                                    <x-adminlte.select name="bank_id_1" id="bank_id_1" label=""
                                        data-type="App\Models\Bank" :options="$banks" />
                                </div>
                            </div>

                            {{-- Cuenta Pago 1 (Polimórfico: BankAccount) --}}
                            <div class="mb-2 d-none" id="container_bank_account_id_1">
                                <div class="compact-select-wrapper">
                                    <label class="compact-select-label">Cuenta</label>
                                    <x-adminlte.select name="bank_account_id_1" id="bank_account_id_1" label=""
                                        data-type="App\Models\BankAccount" :options="$bankAccounts" />
                                </div>
                            </div>

                            {{-- Monto recibido 1 --}}
                            <x-bootstrap.compact-input id="amount_received_1" name="amount_received_1" type="number"
                                label="Monto Recibido" step="0.01" prefix="$" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                {{-- Bloque Pago 2 --}}
                <div class="col-md-6">
                    <div class="card card-outline card-info shadow-sm border bg-light">
                        <div class="card-body p-3">
                            <label class="small text-muted mb-2 d-block text-uppercase fw-bold">
                                <i class="fas fa-circle-2 me-1"></i> Método 2
                            </label>

                            {{-- Tipo de pago 2 --}}
                            <div class="mb-2">
                                <x-adminlte.select name="payment_type_2" id="payment_type_2" label=""
                                    :options="$paymentTypes" :showPlaceholder="false" />
                            </div>

                            {{-- Banco Pago 2 (Polimórfico: Bank) --}}
                            <div class="mb-2 d-none" id="container_bank_id_2">
                                <div class="compact-select-wrapper">
                                    <label class="compact-select-label">Banco</label>
                                    <x-adminlte.select name="bank_id_2" id="bank_id_2" label=""
                                        data-type="App\Models\Bank" :options="$banks" />
                                </div>
                            </div>

                            {{-- Cuenta Pago 2 (Polimórfico: BankAccount) --}}
                            <div class="mb-2 d-none" id="container_bank_account_id_2">
                                <div class="compact-select-wrapper">
                                    <label class="compact-select-label">Cuenta</label>
                                    <x-adminlte.select name="bank_account_id_2" id="bank_account_id_2" label=""
                                        data-type="App\Models\BankAccount" :options="$bankAccounts" />
                                </div>
                            </div>

                            {{-- Monto recibido 2 --}}
                            <x-bootstrap.compact-input id="amount_received_2" name="amount_received_2" type="number"
                                label="Monto Recibido" step="0.01" prefix="$" placeholder="0.00" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resumen de totales dual payment --}}
            <div class="col-12 mt-2">
                <div class="alert alert-light border mb-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-muted">Total a Cobrar:</span>
                        <span class="fw-bold h5 mb-0 text-success">
                            $ <span id="total_paid_display">0.00</span>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <span class="text-muted small">Método 1:</span>
                        <span class="text-dark">$ <span id="payment_1_summary">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Método 2:</span>
                        <span class="text-dark">$ <span id="payment_2_summary">0.00</span></span>
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

        {{-- Inputs ocultos --}}
        <input type="hidden" id="hidden_order_id" name="order_id">
        <input type="hidden" id="hidden_user_id" name="user_id" value="{{ auth()->id() }}">
        <input type="hidden" id="total_amount" name="total_amount">
        <input type="hidden" id="total_amount_usd_hidden" name="total_amount_usd">
        <input type="hidden" id="is_dual_payment" name="is_dual_payment" value="0">
    </div>
</div>
