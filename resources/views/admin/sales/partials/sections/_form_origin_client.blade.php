<div class="d-flex flex-column gap-2">
    {{-- Sucursal --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label">
            Sucursal (Origen) <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="branch_id" label="" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $sale->branch_id ?? auth()->user()->branch_id)" required />
    </div>
    {{-- Tipo de transacción --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label">
            Tipo de Transacción <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="sale_type" label="" :options="$saleTypeOptions" :value="old('sale_type', $sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value)" :showPlaceholder="false"
            required />
    </div>

    {{-- Tipo de reparación (dinámico) --}}
    <div id="repair-type-wrapper" class="compact-select-wrapper d-none">
        <label class="compact-select-label">
            Tipo de Reparación <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="repair_type_id" id="repair_type_id" label="" :options="$repairTypes ?? []" :value="old('repair_type_id', $sale->repair_type_id ?? '')"
            placeholder="Seleccione tipo de reparación" :showPlaceholder="true" />
    </div>

    {{-- Cliente --}}
    <div id="client-select-wrapper" class="compact-select-wrapper">
        <x-adminlte.select-with-action name="client_id" label="Cliente" :options="$clients->pluck('display_name', 'id')->toArray()"
            placeholder="Seleccione un cliente" :value="old('client_id', $sale->client_id ?? $defaultClientId)" buttonColor="primary" buttonIcon="fas fa-user-plus"
            buttonLabel="Nuevo Cliente" buttonTitle="Agregar nuevo cliente" buttonId="btn-new-client" required />
    </div>

    {{-- Estado --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label">
            Estado <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $sale->status?->value ?? null)" :showPlaceholder="false"
            required />
    </div>

    @include('admin.sales.partials._receipt_type', [
        'default' => 'ticket',
    ])
</div>
