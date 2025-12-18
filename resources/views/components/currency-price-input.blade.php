<div class="mb-3 currency-price-input">
    <label for="{{ $name }}_amount" class="form-label">{{ $label }}</label>
    <div class="input-group">

        {{-- Select de Moneda --}}
        <select class="form-select select-custom-turquoise" id="{{ $name }}_currency"
            name="{{ $name }}_currency" style="" {{ $required ? 'required' : '' }}>

            @foreach ($currencyOptions as $value => $text)
                <option value="{{ $value }}" @if ($currencyValue == $value) selected @endif>
                    {{ $text }}
                </option>
            @endforeach
        </select>

        {{-- Input de Monto --}}
        <input type="number" class="form-control" id="{{ $name }}_amount" name="{{ $name }}_amount"
            placeholder="0.00" value="{{ $amountValue }}" step="0.01" {{ $required ? 'required' : '' }}>
    </div>

    {{-- Manejo de errores de Laravel --}}
    @error($name . '_amount')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
    @error($name . '_currency')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
