@props([
    'id',
    'type' => 'text',
    'name',
    'label',
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => null,
    'helpText' => null,
    'errorKey' => null,
    'addonLeft' => null,
    'addonRight' => null,
    'size' => null, // 'sm', 'lg'
    'wrapperClass' => '',
])

@php
    $errorKey = $errorKey ?? $name;
    $inputId = $id ?? 'input_' . Str::random(10);

    // Clases para tamaño
    $sizeClass = '';
    if ($size === 'sm') {
        $sizeClass = 'form-control-sm';
    } elseif ($size === 'lg') {
        $sizeClass = 'form-control-lg';
    }

    // Clase para estado de validación
    $validationClass = '';
    if ($errors->has($errorKey)) {
        $validationClass = 'is-invalid';
    }
@endphp

<div class="compact-input-wrapper {{ $wrapperClass }}">
    <div class="position-relative">
        @if ($addonLeft)
            <div class="input-group-prepend">
                <span class="input-group-text">{!! $addonLeft !!}</span>
            </div>
        @endif

        <input id="{{ $inputId }}" type="{{ $type }}" name="{{ $name }}"
            value="{{ old($name, $value) }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $required ? 'required' : '' }} {{ $disabled ? 'disabled' : '' }} {{ $readonly ? 'readonly' : '' }}
            class="form-control compact-input {{ $sizeClass }} {{ $validationClass }}" {{ $attributes }} />

        <label for="{{ $inputId }}" class="compact-input-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>

        @if ($addonRight)
            <div class="input-group-append">
                <span class="input-group-text">{!! $addonRight !!}</span>
            </div>
        @endif
    </div>

    @if ($helpText)
        <small class="form-text text-muted mt-1 d-block">{{ $helpText }}</small>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-block mt-1">
            {{ $message }}
        </div>
    @enderror
</div>
