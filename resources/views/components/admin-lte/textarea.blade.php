@props(['id', 'name', 'label', 'value' => null, 'required' => false, 'rows' => 3, 'placeholder' => null])

<div class="form-floating mb-3">
    <textarea id="{{ $id }}" name="{{ $name }}"
        class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" placeholder="{{ $placeholder ?? $label }}"
        rows="{{ $rows }}" {{ $required ? 'required' : '' }} {{ $attributes }}>{{ old($name, $value) }}</textarea>

    <label for="{{ $id }}">{{ $label }}</label>

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
