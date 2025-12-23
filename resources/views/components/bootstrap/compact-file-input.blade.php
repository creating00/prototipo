@props([
    'id' => null,
    'name',
    'label',
    'required' => false,
    'accept' => null,
    'helpText' => null,
    'disabled' => false,
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? 'file_' . Str::random(10);
    $hasError = $errors->has($name);
@endphp

<div class="compact-file-wrapper">
    <label for="{{ $inputId }}" class="compact-file-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <input id="{{ $inputId }}" type="file" name="{{ $name }}"
        class="form-control form-control-sm {{ $hasError ? 'is-invalid' : '' }}" {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }} @if ($accept) accept="{{ $accept }}" @endif
        {{ $attributes }}>

    @if ($helpText)
        <small class="form-text text-muted d-block mt-1">
            {{ $helpText }}
        </small>
    @endif

    @error($name)
        <div class="invalid-feedback d-block mt-1">
            {{ $message }}
        </div>
    @enderror
</div>
