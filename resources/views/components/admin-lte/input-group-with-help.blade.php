<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <div class="input-group">
        @if ($prependText)
            <span id="{{ $id }}-prepend" class="input-group-text">{{ $prependText }}</span>
        @endif

        <input id="{{ $id }}" type="{{ $type }}"
            class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" name="{{ $name }}"
            value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }}
            aria-describedby="{{ $id }}-prepend {{ $id }}-help" {{ $attributes }} />
    </div>

    @if ($helpText)
        <div id="{{ $id }}-help" class="form-text">{{ $helpText }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
