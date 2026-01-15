<div class="d-flex flex-column gap-2">
    {{-- Sucursal Origen --}}
    @if ($isEdit)
        <div class="compact-select-wrapper">
            <label class="compact-select-label fw-bold small">
                Sucursal (Origen) <span class="text-danger">*</span>
            </label>
            <x-adminlte.select name="branch_id" label="" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $order->branch_id ?? auth()->user()->branch_id)" required />
        </div>
    @else
        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
    @endif

    {{-- Sucursal Destinataria --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal Destinataria <span class="text-danger">*</span>
        </label>
        <x-adminlte.select :name="$isEdit ? 'customer_id' : 'branch_recipient_id'" label="" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old($isEdit ? 'customer_id' : 'branch_recipient_id', $isEdit ? $order->customer_id : null)" required />
    </div>

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
