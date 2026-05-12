<div class="{{ $containerClass }}">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @php
        $isReadonly = $attributes->has('readonly');
        $classes = 'form-select form-select-custom ' . ($errors->has($name) ? 'is-invalid' : '');
    @endphp

    <select @if (!$isReadonly) name="{{ $name }}" @endif id="{{ $attributes->get('id', $name) }}"
        {{ $attributes->merge(['class' => $classes]) }} {{ $required ? 'required' : '' }}>
        @if ($placeholder)
            <option value="" {{ is_null($selected) || $selected === '' ? 'selected' : '' }}
                {{ $disablePlaceholder ? 'disabled' : '' }}>
                {{ $placeholder }}
            </option>
        @endif

        @foreach ($options as $value => $text)
            <option value="{{ $value }}"
                {{ (string) old($name, $selected) === (string) $value ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @if ($isReadonly)
        <input type="hidden" name="{{ $name }}" value="{{ old($name, $selected) }}">
    @endif

    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
