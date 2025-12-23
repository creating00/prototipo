   {{-- Totales --}}
   <div id="sale-total-wrapper">
       <x-bootstrap.compact-input id="total_amount" name="total_amount" type="number" label="Total del Pedido"
           step="0.01" readonly prefix="$" value="{{ old('total_amount', $sale->total_amount ?? 0) }}" />
   </div>

   <div id="repair-amount-wrapper" class="d-none">
       <x-bootstrap.compact-input id="repair_amount" name="repair_amount" type="number" label="Costo de la Reparación"
           step="0.01" prefix="$" value="{{ old('repair_amount', $sale->repair_amount ?? '') }}"
           oninput="window.salePayment?.setSaleTotalFromRepair()" required />
   </div>

   <hr class="my-3">

   {{-- Datos de pago --}}
   <div class="row g-3 equal-height-selects align-items-end">
       <div class="col-md-4">
           <x-bootstrap.compact-input id="sale_date" name="sale_date" type="date" label="Fecha de Venta"
               value="{{ $saleDate }}" required />
       </div>

       <div class="col-md-4">
           <x-admin-lte.select name="payment_type" label="Tipo de Pago" :options="$paymentOptions" :value="old('payment_type', $sale->payment_type ?? 1)" required />
       </div>

       <div class="col-md-4">
           <x-bootstrap.compact-input id="amount_received" name="amount_received" type="number" label="Monto Recibido"
               step="0.01" prefix="$" value="{{ old('amount_received', $sale->amount_received ?? 0) }}" required
               oninput="window.salePayment?.calculateChangeAndBalance()" />
       </div>

       <div class="col-md-4">
           <x-bootstrap.compact-input id="change_returned" name="change_returned" type="number" label="Cambio Devuelto"
               step="0.01" prefix="$" value="{{ old('change_returned', $sale->change_returned ?? 0) }}"
               readonly />
       </div>

       <div class="col-md-4">
           <x-bootstrap.compact-input id="remaining_balance" name="remaining_balance" type="number"
               label="Saldo Pendiente" step="0.01" prefix="$"
               value="{{ old('remaining_balance', $sale->remaining_balance ?? 0) }}" readonly />
       </div>

       <div class="col-md-4">
           <div class="form-group">
               <label>Estado del Pago</label>
               <div id="payment_status_indicator" class="mt-2">
                   <span class="badge bg-secondary">Esperando datos...</span>
               </div>
           </div>
       </div>
   </div>
