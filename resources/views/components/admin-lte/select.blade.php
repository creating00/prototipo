<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @php
        $current = old($name, $selected);
        $selectedItems = is_array($current) ? $current : ($current === null ? [] : [(string) $current]);
        $inputName = $multiple ? $name . '[]' : $name;

        // Comillas seguras para Alpine
        $placeholderJson = json_encode($placeholder ?? 'Seleccione una opción');
        $searchEnabledJs = $searchEnabled ? 'true' : 'false';
        $removeItemButtonJs = $multiple ? 'true' : 'false';

        $showPlaceholderJs = $showPlaceholder ? 'true' : 'false';
    @endphp

    <select x-data="{ choices: null }"
        x-init='choices  = new Choices($el, {
            searchEnabled: {{ $searchEnabledJs }},
            itemSelectText: "",
            placeholder: {{ $showPlaceholderJs }},
            placeholderValue: {!! $placeholderJson !!},
            removeItemButton: {{ $removeItemButtonJs }},
            shouldSort: false
        }); 
        $el._choices = choices;

        $el.addEventListener("change", () => {
            if ($el.value !== "") {
                // Quitar clase de error del select (que afecta al CSS de Choices)
                $el.classList.remove("is-invalid");
                
                // Ocultar el mensaje de error de Laravel que está debajo
                let errorMsg = $el.closest(".mb-3").querySelector(".invalid-feedback");
                if (errorMsg) errorMsg.style.display = "none";
            }
        });'
        name="{{ $inputName }}" id="{{ $name }}" class="form-control @error($name) is-invalid @enderror"
        {{ $required ? 'required' : '' }} {{ $multiple ? 'multiple' : '' }} {{ $attributes }}>
        @if ($placeholder && !$multiple)
            <option value="" disabled selected hidden>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $key => $value)
            @php $keyStr = (string)$key; @endphp
            <option value="{{ $key }}" {{ in_array($keyStr, $selectedItems, true) ? 'selected' : '' }}>
                {{ $value }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
