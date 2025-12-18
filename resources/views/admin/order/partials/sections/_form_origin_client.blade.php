<div class="row g-3 mb-4 equal-height-selects align-items-end">

    <!-- Sucursal Origen -->
    <div class="col-md-4">
        <x-admin-lte.select name="branch_id" label="Sucursal (Origen)" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $order->branch_id ?? auth()->user()->branch_id)" required />
    </div>

    <!-- Cliente Destinatario -->
    <div class="col-md-4" id="client-select-wrapper">
        <x-admin-lte.select-with-action name="client_id" label="Cliente" :options="$clients->pluck('display_name', 'id')->toArray()"
            placeholder="Seleccione un cliente" :value="old('client_id', $order->customer_id ?? null)" buttonColor="primary" buttonIcon="fas fa-user-plus"
            buttonLabel="Nuevo Cliente" buttonTitle="Agregar nuevo cliente" buttonId="btn-new-client" required />
    </div>

    <!-- Estado -->
    <div class="col-md-4">
        <x-admin-lte.select name="status" label="Estado del Pedido" :options="$statusOptions" :value="old('status', $order->status->value ?? 0)" required />
    </div>

</div>
