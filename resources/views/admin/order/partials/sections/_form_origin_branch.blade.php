<div class="d-flex flex-column gap-2">

    {{-- Sucursal Proveedora --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">Sucursal Proveedora</label>
        <x-adminlte.select name="branch_id" id="branch_id" :options="$destinationBranches->pluck('name', 'id')->toArray()" :value="old('branch_id', $order->branch_id ?? '')" required />
    </div>

    {{-- Sucursal Solicitante --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">Sucursal Solicitante</label>
        @if ($isEdit)
            <x-adminlte.select name="customer_id" id="customer_id" :options="$branches->pluck('name', 'id')->toArray()" :value="old('customer_id', $order->customer_id ?? '')" required />
        @else
            <x-adminlte.select name="customer_id" id="customer_id" :options="$destinationBranches->pluck('name', 'id')->prepend('Sucursal Actual (Mi Sucursal)', auth()->user()->branch_id)->toArray()" :value="old('customer_id', auth()->user()->branch_id)" required />
        @endif
    </div>

    {{-- Fecha del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">Fecha del Pedido</label>
        <input type="date" name="created_at" id="created_at" class="form-control form-control-sm"
            value="{{ old('created_at', isset($order->created_at) ? $order->created_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>

    <input type="hidden" name="customer_type" value="App\Models\Branch">

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? \App\Enums\OrderStatus::Pending->value)" required />
    </div>

    {{-- Estado de Pago --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado de Pago
        </label>
        @if (isset($isEdit) && $isEdit)
            <x-adminlte.select name="payment_status" id="payment_status" label="" :options="[2 => 'Pendiente', 1 => 'Pagado']" :value="old('payment_status', $order->payment_status ?? 2)" required />
        @else
            <div>
                <span class="badge badge-custom badge-custom-pastel-yellow fs-6">Pendiente</span>
                <input type="hidden" name="payment_status" value="2">
            </div>
        @endif
    </div>

    {{-- Botón Auto-Pedido --}}
    <div class="mt-2">
        <button type="button" id="btn-auto-pedido" class="btn btn-outline-primary btn-sm w-100 shadow-sm">
            <i class="fas fa-magic me-1"></i> Cargar Auto-Pedido por Stock
        </button>
    </div>
</div>
