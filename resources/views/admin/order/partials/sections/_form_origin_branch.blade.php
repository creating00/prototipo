<div class="d-flex flex-column gap-2">

    {{-- Sucursal Proveedora --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">Sucursal Proveedora</label>
        <x-adminlte.select name="branch_id" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('branch_id', $order->branch_id ?? '')" required />
    </div>

    {{-- Sucursal Solicitante --}}
    @if ($isEdit)
        <div class="compact-select-wrapper">
            <label class="compact-select-label fw-bold small">Sucursal Solicitante</label>
            <x-adminlte.select name="customer_id" :options="$branches->pluck('name', 'id')->toArray()" :value="old('customer_id', $order->customer_id ?? '')" required />
        </div>
    @else
        <input type="hidden" name="customer_id" value="{{ auth()->user()->branch_id }}">
    @endif

    <input type="hidden" name="customer_type" value="App\Models\Branch">

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
