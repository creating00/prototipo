<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="paymentModalLabel">
                    <i class="fas fa-credit-card mr-2"></i>Procesar Pago
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Resumen de la Orden -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Resumen de la Orden</h6>
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>N° Orden:</span>
                                <strong id="modalOrderId">-</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total a Pagar:</span>
                                <strong class="text-success" id="modalOrderTotal">$0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Cliente:</span>
                                <strong id="modalClientName">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Información del Pago</h6>
                        <div class="border rounded p-3">
                            <div class="form-group">
                                <label for="paymentType" class="font-weight-bold">Tipo de Pago *</label>
                                <select class="form-control" id="paymentType" name="paymentType" required>
                                    <option value="">Seleccione tipo de pago</option>
                                    @foreach (App\Enums\PaymentType::forSelect() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Campo dinámico para monto si es diferente al total -->
                            <div class="form-group" id="amountField" style="display: none;">
                                <label for="paymentAmount" class="font-weight-bold">Monto a Pagar</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" class="form-control" id="paymentAmount" min="0"
                                        step="0.01" placeholder="0.00">
                                </div>
                                <small class="form-text text-muted">
                                    Deje vacío para pagar el total completo
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información adicional según tipo de pago -->
                <div id="paymentDetails" style="display: none;">
                    <h6 class="font-weight-bold mb-3">Detalles del Pago</h6>
                    <div class="row">
                        <!-- Campos para tarjeta -->
                        <div class="col-md-6" id="cardFields" style="display: none;">
                            <div class="form-group">
                                <label for="cardNumber">Número de Tarjeta</label>
                                <input type="text" class="form-control" id="cardNumber"
                                    placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="form-group">
                                <label for="cardHolder">Titular de la Tarjeta</label>
                                <input type="text" class="form-control" id="cardHolder" placeholder="JUAN PEREZ">
                            </div>
                        </div>

                        <!-- Campos para transferencia -->
                        <div class="col-md-6" id="transferFields" style="display: none;">
                            <div class="form-group">
                                <label for="referenceNumber">Número de Referencia</label>
                                <input type="text" class="form-control" id="referenceNumber"
                                    placeholder="Número de transacción">
                            </div>
                            <div class="form-group">
                                <label for="bankName">Banco</label>
                                <input type="text" class="form-control" id="bankName"
                                    placeholder="Nombre del banco">
                            </div>
                        </div>

                        <!-- Campos para cheque -->
                        <div class="col-md-6" id="checkFields" style="display: none;">
                            <div class="form-group">
                                <label for="checkNumber">Número de Cheque</label>
                                <input type="text" class="form-control" id="checkNumber"
                                    placeholder="Número de cheque">
                            </div>
                            <div class="form-group">
                                <label for="bankNameCheck">Banco Emisor</label>
                                <input type="text" class="form-control" id="bankNameCheck"
                                    placeholder="Nombre del banco">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" id="confirmPayment">
                    <i class="fas fa-check mr-1"></i>Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-content {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-radius: 10px 10px 0 0;
    }

    .border {
        border: 1px solid #e3e6f0 !important;
    }

    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
</style>
