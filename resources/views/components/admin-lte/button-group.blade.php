@php
    $baseClasses = 'btn-group';
    $verticalClass = $vertical ? 'btn-group-vertical' : '';

    $classes = implode(' ', array_filter([$baseClasses, $verticalClass, $class]));
@endphp

<div role="group" aria-label="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
