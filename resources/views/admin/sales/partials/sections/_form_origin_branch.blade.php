<div class="d-flex flex-column gap-2">
    <input type="hidden" name="branch_id" id="current_branch_id" value="{{ $originBranch->id }}">
    <input type="hidden" name="sale_type" value="{{ $sale->sale_type->value ?? \App\Enums\SaleType::Sale->value }}">
    {{-- Sucursal destinataria --}}
    <x-adminlte.select name="customer_id" label="Sucursal Destino" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('customer_id', $sale->customer_id ?? null)" required />

    {{-- Estado --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label">
            Estado de la Venta <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $sale->status?->value ?? null)" :showPlaceholder="false"
            required />
    </div>
</div>
