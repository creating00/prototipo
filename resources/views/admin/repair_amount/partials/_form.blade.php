@props([
    'repairAmount' => null,
    'repairTypes' => [],
])

@push('styles')
    {{-- @vite('resources/css/modules/repair-amounts/repair-amounts-styles.css') --}}
    @vite('resources/css/modules/branches/branches-styles.css')
@endpush

<div class="row g-3">
    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
    {{-- Tipo de reparación --}}
    <div class="col-md-6">
        <div class="compact-select-wrapper">
            <label class="compact-select-label">
                Tipo de Reparación <span class="text-danger">*</span>
            </label>

            <x-adminlte.select name="repair_type" :options="collect($repairTypes)
                ->mapWithKeys(
                    fn($type) => [
                        $type->value => $type->label(),
                    ],
                )
                ->toArray()" placeholder="Seleccione un tipo de reparación"
                :value="old('repair_type', $repairAmount?->repair_type?->value)" required />
        </div>
    </div>

    {{-- Monto --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="amount" name="amount" type="number" step="0.01" label="Monto"
            placeholder="Ej: 15000" :value="old('amount', $repairAmount->amount ?? '')" required />
    </div>
</div>
