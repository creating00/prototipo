<div class="row g-3 equal-height-selects align-items-end">
    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
    <!-- Sucursal Destinataria -->
    <div class="col-md-4" id="branch-select-wrapper">
        <x-admin-lte.select name="branch_recipient_id" label="Sucursal Destinataria" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('branch_recipient_id', $sale->branch_recipient_id ?? null)"
            required />
    </div>

    <!-- Estado -->
    <div class="col-md-4">
        <x-admin-lte.select name="status" label="Estado de la Venta" :options="$statusOptions" :value="old('status', $sale->status?->value ?? null)"
            :showPlaceholder="false" required />
    </div>
</div>
