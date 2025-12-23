<div class="compact-textarea-wrapper {{ $wrapperClass }}">
    <div class="position-relative">
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
            class="form-control compact-textarea {{ $resizeClass }} {{ $validationClass }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            @if ($required) required @endif @if ($disabled) disabled @endif
            @if ($readonly) readonly @endif {{ $attributes }}>{{ $value }}</textarea>

        <label for="{{ $id }}" class="compact-textarea-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    </div>

    @if ($helpText)
        <small class="form-text text-muted mt-1 d-block">
            {{ $helpText }}
        </small>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-block mt-1">
            {{ $message }}
        </div>
    @enderror
</div>
