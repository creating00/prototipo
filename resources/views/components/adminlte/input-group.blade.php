@props([
    'id',
    'type' => 'text',
    'name',
    'label',
    'icon' => null,
    'iconPosition' => 'right', // 'left' o 'right'
    'value' => null,
    'required' => false,
    'autofocus' => false,
])

<div class="input-group mb-3">

    {{-- Ícono a la izquierda --}}
    @if ($icon && $iconPosition === 'left')
        <span class="input-group-text">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    @endif

    <div class="form-floating flex-grow-1">
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}"
            class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" placeholder="{{ $label }}"
            value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $autofocus ? 'autofocus' : '' }}
            {{ $attributes }} />
        <label for="{{ $id }}">{{ $label }}</label>
    </div>

    {{-- Ícono a la derecha --}}
    @if ($icon && $iconPosition === 'right')
        <span class="input-group-text">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
