<div class="d-flex flex-column gap-2">
    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">

    {{-- Sucursal destinataria --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label">
            Sucursal Destinataria <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="branch_recipient_id" label="" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('branch_recipient_id', $sale->branch_recipient_id ?? null)" required />
    </div>

    {{-- Estado --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label">
            Estado de la Venta <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $sale->status?->value ?? null)" :showPlaceholder="false"
            required />
    </div>
</div>
