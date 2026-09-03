<div class="d-flex flex-column gap-2">
    {{-- Sucursal --}}
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label">
            Sucursal (Origen) <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="branch_id" label="" :options="$branches->pluck('name', 'id')->toArray()" :value="old('branch_id', $sale->branch_id ?? auth()->user()->branch_id)" required />
    </div>
    @php
        $currentSaleType = (string) old('sale_type', $sale->sale_type?->value ?? \App\Enums\SaleType::Sale->value);
        $currentRepairTypeId = (string) old('repair_type_id', $sale->repair_type_id ?? '');
    @endphp

    {{-- Tipo de transacción con Selección Directa (Radio Buttons) --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label mb-1">
            Tipo de Transacción <span class="text-danger">*</span>
        </label>
        <div class="btn-group w-100 transaction-type-group" role="group" aria-label="Tipo de Transacción">
            <input type="radio" class="btn-check" name="sale_type" id="sale_type_sale" value="1"
                autocomplete="off" {{ $currentSaleType === '1' ? 'checked' : '' }} required>
            <label class="btn btn-outline-primary transaction-radio-btn" for="sale_type_sale">
                <i class="fas fa-shopping-cart me-1"></i> Venta
            </label>

            <input type="radio" class="btn-check" name="sale_type" id="sale_type_repair" value="2"
                autocomplete="off" {{ $currentSaleType === '2' ? 'checked' : '' }} required>
            <label class="btn btn-outline-purple transaction-radio-btn" for="sale_type_repair">
                <i class="fas fa-tools me-1"></i> Reparación
            </label>
        </div>
    </div>

    {{-- Tipo de reparación con Burbujas / Chips interactivos --}}
    <div id="repair-type-wrapper" class="compact-select-wrapper {{ $currentSaleType === '2' ? '' : 'd-none' }}">
        <label class="compact-select-label mb-1">
            Tipo de Reparación <span class="text-danger">*</span>
        </label>
        <div class="repair-bubbles-container d-flex flex-wrap gap-1">
            @php
                $repairIcons = [
                    1 => 'fas fa-mobile-alt',             // Modulo
                    2 => 'fas fa-battery-three-quarters', // Batería
                    3 => 'fas fa-plug',                   // Pin de carga
                    4 => 'fas fa-shield-alt',             // Glass
                    5 => 'fas fa-microchip',              // Microsoldadura
                    6 => 'fas fa-wrench',                 // Otro
                ];
            @endphp
            @foreach (\App\Enums\RepairType::cases() as $repairCase)
                @php
                    $val = (string) $repairCase->value;
                    $icon = $repairIcons[$repairCase->value] ?? 'fas fa-tools';
                    $isChecked = ($currentRepairTypeId === $val);
                @endphp
                <input type="radio" class="btn-check repair-bubble-input" name="repair_type_id"
                    id="repair_type_{{ $val }}" value="{{ $val }}"
                    data-label="{{ $repairCase->label() }}"
                    autocomplete="off" {{ $isChecked ? 'checked' : '' }}>
                <label class="btn btn-outline-secondary btn-sm repair-bubble-chip" for="repair_type_{{ $val }}"
                    title="{{ $repairCase->label() }}">
                    <i class="{{ $icon }} me-1"></i><span>{{ $repairCase->label() }}</span>
                </label>
            @endforeach
        </div>
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
