<div class="d-flex flex-nowrap gap-1 justify-content-center">
    @foreach ($cases as $case)
        @php
            $uniqueId = "target_{$categoryId}_{$case->value}";
            $isChecked = $currentValue === $case->value;
        @endphp
        <div>
            <input class="btn-check btn-update-target" type="radio" name="target_{{ $categoryId }}"
                id="{{ $uniqueId }}" data-id="{{ $categoryId }}" value="{{ (int) $case->value }}"
                {{ $isChecked ? 'checked' : '' }}>
            <label class="btn btn-sm btn-outline-secondary" for="{{ $uniqueId }}">
                {{ $case->label() }}
            </label>
        </div>
    @endforeach
</div>
