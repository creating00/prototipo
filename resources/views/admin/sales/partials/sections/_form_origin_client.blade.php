<div class="row g-3 mb-4 equal-height-selects align-items-end">

    <div class="col-md-3">
        <x-admin-lte.select name="branch_id" label="Sucursal (Origen)" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $sale->branch_id ?? auth()->user()->branch_id)" required />
    </div>

    <div class="col-md-3">
        <x-admin-lte.select name="sale_type" label="Tipo de Transacción" :options="$saleTypeOptions" :value="old('sale_type', $sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value)"
            :showPlaceholder="false" required />
    </div>

    <div class="col-md-3" id="client-select-wrapper">
        <x-admin-lte.select-with-action name="client_id" label="Cliente" :options="$clients->pluck('display_name', 'id')->toArray()"
            placeholder="Seleccione un cliente" :value="old('client_id', $sale->customer_id ?? null)" buttonColor="primary" buttonIcon="fas fa-user-plus"
            buttonLabel="Nuevo Cliente" buttonTitle="Agregar nuevo cliente" buttonId="btn-new-client" required />
    </div>

    <div class="col-md-3">
        <x-admin-lte.select name="status" label="Estado de la Venta" :options="$statusOptions" :value="old('status', $sale->status?->value ?? null)"
            :showPlaceholder="false" required />
    </div>

</div>
