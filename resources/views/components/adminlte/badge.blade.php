@props([
    'type' => 'secondary',
])

<span {{ $attributes->merge(['class' => "badge bg-{$type}"]) }}>
    {{ $slot }}
</span>
