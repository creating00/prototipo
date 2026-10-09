@php
    $currentBranchId = old('branch_id', $currentBranchId ?? auth()->user()?->branch_id);
    $currentBranch = collect($branches ?? [])->firstWhere('id', (int) $currentBranchId);
@endphp

<div class="d-flex flex-column gap-2">
    @if ($isMultiDestination)
        @php
            $manualBranchesData = $destinationBranches->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name, 'province_id' => $branch->province_id, 'province_name' => $branch->province?->name])->values();
        @endphp
        <input type="hidden" name="is_multi_destination" id="is_multi_destination" value="1">
        <input type="hidden" id="transfer-destinations-data" value='@json($manualBranchesData)'>
        <input type="hidden" name="branch_id" id="current_branch_id" value="{{ $currentBranchId }}">
        <div class="compact-select-wrapper">
            <label for="destination_province_id" class="compact-select-label fw-bold small">Provincia de destino</label>
            <select id="destination_province_id" name="destination_province_id" class="form-select">
                <option value="">Seleccione una provincia</option>
                @foreach ($destinationProvinces as $province)
                    <option value="{{ $province->id }}" @selected((string) old('destination_province_id', $order?->items->first()?->destinationBranch?->province_id) === (string) $province->id)>{{ $province->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Búsqueda y precios de {{ $currentBranch?->name ?? auth()->user()?->branch?->name }}. La provincia filtra los destinos de los nuevos renglones.</small>
        </div>
    @else
    <div id="branch-select-wrapper" class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Sucursal (Origen) <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control form-control-sm bg-light fw-bold" value="{{ $currentBranch?->name ?? auth()->user()?->branch?->name ?? 'Mi Sucursal' }}" readonly>
        <input type="hidden" name="branch_id" id="current_branch_id" value="{{ $currentBranchId }}">
    </div>
    @endif

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
        <x-adminlte.select name="target_branch_id" label="" :options="['' => 'Seleccione una sucursal de referencia'] + ($destinationBranches ?? collect())->pluck('name', 'id')->toArray()" :value="old('target_branch_id', $order?->target_branch_id)" :required="$isMultiDestination" />
    </div>

    {{-- Estado del Pedido --}}
    <div class="compact-select-wrapper">
        <label class="compact-select-label fw-bold small">
            Estado del Pedido <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="status" label="" :options="$statusOptions" :value="old('status', $order->status->value ?? 1)" required />
    </div>
</div>
