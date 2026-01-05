<div class="d-flex flex-column gap-2">
    {{-- Campo oculto para la sucursal origen (la del usuario) --}}
    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">

    {{-- Sucursal Destinataria --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal Destinataria <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="branch_recipient_id" label="" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('branch_recipient_id', $order->branch_recipient_id ?? null)" required />
    </div>

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
