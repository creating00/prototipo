<div class="currency-price-input compact-input-group-wrapper">
    <div class="input-group">
        <select class="form-select select-custom-turquoise" id="{{ $name }}_currency"
            name="{{ $name }}_currency" {{ $required ? 'required' : '' }}>
            @foreach ($currencyOptions as $value => $text)
                <option value="{{ $value }}" @selected($currencyValue == $value)>
                    {{ $text }}
                </option>
            @endforeach
        </select>

        <div class="currency-input-wrapper">
            <label class="compact-input-label">{{ $label }}</label>

            <input type="number" onkeydown="return event.key !== '-'" class="form-control" id="{{ $name }}_amount" name="{{ $name }}_amount"
                value="{{ $amountValue }}" step="0.01" min="0" {{ $required ? 'required' : '' }}>
        </div>
    </div>

    {{-- Manejo de errores de Laravel --}}
    @error($name . '_amount')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
    @error($name . '_currency')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
