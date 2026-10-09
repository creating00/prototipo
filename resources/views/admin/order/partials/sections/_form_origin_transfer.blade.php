<input type="hidden" name="is_multi_destination" id="is_multi_destination" value="1">
<input type="hidden" name="branch_id" value="{{ $originBranch->id }}">
<input type="hidden" id="current_branch_id" value="{{ $originBranch->id }}">
@php
    $transferBranchesData = $destinationBranches->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name, 'province_id' => $branch->province_id, 'province_name' => $branch->province?->name])->values();
@endphp
<input type="hidden" id="transfer-destinations-data" value='@json($transferBranchesData)'>

<div class="d-flex flex-column gap-3">
    <div>
        <label for="transfer-origin-name" class="form-label small fw-bold">Sucursal de origen</label>
        <input id="transfer-origin-name" class="form-control" value="{{ $originBranch->name }}" readonly>
    </div>
    <div>
        <label for="destination_province_id" class="form-label small fw-bold">Provincia de destino</label>
        <select id="destination_province_id" name="destination_province_id" class="form-select">
            <option value="">Seleccione una provincia</option>
            @foreach ($destinationProvinces as $province)
                <option value="{{ $province->id }}" @selected((string) old('destination_province_id', $order?->items->first()?->destinationBranch?->province_id) === (string) $province->id)>{{ $province->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Filtra los destinos de los nuevos productos. Cada renglón conserva su destino.</small>
    </div>
    <div>
        <label for="created_at" class="form-label small fw-bold">Fecha del pedido</label>
        <input type="date" name="created_at" id="created_at" class="form-control" value="{{ old('created_at', $order?->created_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
    </div>
    <div>
        <label for="status" class="form-label small fw-bold">Estado del pedido</label>
        <select id="status" name="status" class="form-select" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected((int) old('status', $order?->status?->value ?? \App\Enums\OrderStatus::Pending->value) === (int) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <span class="form-label small fw-bold d-block">Estado de pago</span>
        <span class="{{ $order?->payment_status_badge_class ?? 'badge badge-custom badge-custom-pastel-yellow' }}">{{ $order?->payment_status_label ?? 'Pendiente' }}</span>
    </div>
</div>
