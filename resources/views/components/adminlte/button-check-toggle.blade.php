@php
    $colorClass = "btn-outline-{$color}";
    $labelClasses = implode(' ', array_filter(['btn', $colorClass, $class]));
@endphp

<input type="checkbox" class="btn-check" id="{{ $id }}" autocomplete="off"
    @if ($checked) checked @endif
    {{ $attributes->filter(fn($value, $key) => !in_array($key, ['class'])) }}>

<label class="{{ $labelClasses }}" for="{{ $id }}">
    {{ $slot }}
</label>
