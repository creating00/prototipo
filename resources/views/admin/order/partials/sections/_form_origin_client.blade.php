<div class="d-flex flex-column gap-2">
    {{-- Sucursal Origen (Bloqueada siempre a la sucursal actual para pedidos manuales) --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal (Origen) <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control form-control-sm bg-light fw-bold" value="{{ auth()->user()?->branch?->name ?? 'Mi Sucursal' }}" readonly>
        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
    </div>

    {{-- Cliente Destinatario --}}
    <div id="client-select-wrapper" class="compact-select-wrapper">
        <x-adminlte.select-with-action name="client_id" label="Cliente" :options="$clients->pluck('display_name', 'id')->toArray()"
            placeholder="Seleccione un cliente" :value="old('client_id', $order->customer_id ?? $defaultClientId)" buttonColor="primary" buttonIcon="fas fa-user-plus"
            buttonLabel="F2" buttonTitle="Agregar nuevo cliente" buttonId="btn-new-client" required />
    </div>

    {{-- Sucursal a la que se hace el pedido (Referencia) --}}
    <div id="target-branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal a la que le hicieron el pedido (Referencia)
        </label>
        <x-adminlte.select name="target_branch_id" label="" :options="['' => 'Sin sucursal de referencia'] + ($destinationBranches ?? collect())->pluck('name', 'id')->toArray()" :value="old('target_branch_id')" />
    </div>

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
