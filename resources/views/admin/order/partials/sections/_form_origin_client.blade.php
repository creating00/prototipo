<div class="d-flex flex-column gap-2">
    {{-- Sucursal Origen --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal (Origen) <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="branch_id" label="" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $order->branch_id ?? auth()->user()->branch_id)" required />
    </div>

    {{-- Cliente Destinatario --}}
    <div id="client-select-wrapper" class="compact-select-wrapper">
        {{-- Aquí mantenemos el label interno del componente select-with-action porque ya maneja su propia estructura de botón --}}
        <x-adminlte.select-with-action name="client_id" label="Cliente" :options="$clients->pluck('display_name', 'id')->toArray()"
            placeholder="Seleccione un cliente" :value="old('client_id', $order->customer_id ?? $defaultClientId)" buttonColor="primary" buttonIcon="fas fa-user-plus"
            buttonLabel="F2" buttonTitle="Agregar nuevo cliente" buttonId="btn-new-client" required />
    </div>

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
