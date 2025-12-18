@php
    $baseClasses = 'btn';

    if (str_starts_with($color, 'custom-')) {
        if ($outline) {
            $colorClass = "btn-custom-outline btn-$color";
        } else {
            $colorClass = "btn-custom btn-$color";
        }
    } else {
        $validColor = in_array($color, $allowedColors ?? []) ? $color : 'primary';
        $colorClass = $outline ? "btn-outline-{$validColor}" : "btn-{$validColor}";
    }

    $sizeClass = $size ? "btn-{$size}" : '';
    $disabledClass = $disabled ? 'disabled' : '';

    $classes = implode(' ', array_filter([$baseClasses, $colorClass, $sizeClass, $disabledClass, $class]));
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}
    @if ($disabled) disabled @endif>
    @if ($icon && $iconPosition === 'left')
        <i class="{{ $icon }}"></i>
    @endif

    <span>{{ $slot }}</span>

    @if ($icon && $iconPosition === 'right')
        <i class="{{ $icon }}"></i>
    @endif
</button>
