@props(['name', 'value' => null, 'checked' => false, 'label'])

<div class="form-check">
    <input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $name }}"
        value="{{ $value }}" {{ $checked ? 'checked' : '' }} {{ $attributes }} />
    <label class="form-check-label" for="{{ $name }}">
        {!! $label !!}
    </label>
</div>
