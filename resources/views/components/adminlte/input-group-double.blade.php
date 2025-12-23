<div class="input-group mb-3">
    {{-- Primer input --}}
    @if ($firstLabel)
        <div class="form-floating">
            <input id="{{ $firstId }}" type="text"
                class="form-control {{ $errors->has($firstName) ? 'is-invalid' : '' }}" name="{{ $firstName }}"
                placeholder="{{ $firstLabel }}" value="{{ old($firstName, $firstValue) }}" {{ $attributes }} />
            <label for="{{ $firstId }}">{{ $firstLabel }}</label>
        </div>
    @else
        <input id="{{ $firstId }}" type="text"
            class="form-control {{ $errors->has($firstName) ? 'is-invalid' : '' }}" name="{{ $firstName }}"
            value="{{ old($firstName, $firstValue) }}" {{ $attributes }} />
    @endif

    {{-- Separador --}}
    @if ($separator)
        <span class="input-group-text">{{ $separator }}</span>
    @endif

    {{-- Segundo input --}}
    @if ($secondLabel)
        <div class="form-floating">
            <input id="{{ $secondId }}" type="text"
                class="form-control {{ $errors->has($secondName) ? 'is-invalid' : '' }}" name="{{ $secondName }}"
                placeholder="{{ $secondLabel }}" value="{{ old($secondName, $secondValue) }}" {{ $attributes }} />
            <label for="{{ $secondId }}">{{ $secondLabel }}</label>
        </div>
    @else
        <input id="{{ $secondId }}" type="text"
            class="form-control {{ $errors->has($secondName) ? 'is-invalid' : '' }}" name="{{ $secondName }}"
            value="{{ old($secondName, $secondValue) }}" {{ $attributes }} />
    @endif

    {{-- Errores --}}
    @error($firstName)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error($secondName)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
