<div class="mb-3">
    @if ($showLabel && $label)
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label for="{{ $name }}" class="form-label mb-0">
                {{ $label }}
                @if ($required)
                    <span class="text-danger">*</span>
                @endif
            </label>

            @if ($buttonLabel)
                <x-adminlte.button :id="$buttonId" :color="$buttonColor" size="sm" :icon="$buttonIcon"
                    :label="$buttonLabel" :class="$buttonClass" :title="$buttonTitle" />
            @endif

        </div>
    @endif

    <x-adminlte.select :name="$name" :options="$options" :placeholder="$placeholder" :value="$value" :required="$required"
        :searchEnabled="$searchEnabled" :multiple="$multiple" :label="false" :attributes="$attributes ?? []" />
</div>
