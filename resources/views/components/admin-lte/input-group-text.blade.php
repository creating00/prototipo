<div class="input-group mb-3">
    {{-- Prepend --}}
    @if ($prependText)
        <span class="input-group-text">{{ $prependText }}</span>
    @endif

    {{-- Input con o sin label --}}
    @if ($label)
        <div class="form-floating">
            <input id="{{ $id }}" type="{{ $type }}"
                class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" name="{{ $name }}"
                placeholder="{{ $label }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }}
                {{ $autofocus ? 'autofocus' : '' }} {{ $attributes }} />
            <label for="{{ $id }}">{{ $label }}</label>
        </div>
    @else
        <input id="{{ $id }}" type="{{ $type }}"
            class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" name="{{ $name }}"
            value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $autofocus ? 'autofocus' : '' }}
            {{ $attributes }} />
    @endif

    {{-- Append --}}
    @if ($appendText)
        <span class="input-group-text">{{ $appendText }}</span>
    @endif

    {{-- Validaciones --}}
    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
