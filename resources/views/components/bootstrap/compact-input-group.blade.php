@php
    use Illuminate\Support\Str;

    $inputId = $id ?? 'input_' . Str::random(10);
    $hasError = $errors->has($name);
@endphp

<div class="compact-input-group-wrapper">
    <label for="{{ $inputId }}" class="compact-input-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="input-group input-group-sm">
        <input id="{{ $inputId }}" type="{{ $type }}" name="{{ $name }}"
            class="form-control {{ $hasError ? 'is-invalid' : '' }}" placeholder="{{ $placeholder ?? $label }}"
            value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $attributes }}>

        @if ($buttonLabel || $buttonIcon)
            <button type="button" class="btn btn-outline-secondary"
                @if ($buttonOnClick) onclick="{{ $buttonOnClick }}" @endif>
                @if ($buttonIcon)
                    <i class="{{ $buttonIcon }}"></i>
                @endif
                {{ $buttonLabel }}
            </button>
        @endif
    </div>

    @error($name)
        <div class="invalid-feedback d-block mt-1">
            {{ $message }}
        </div>
    @enderror
</div>
