@props([
    'striped' => true,
    'hover' => true,
    'responsive' => true,
])

@php
    $classes = 'table table-sm mb-0';
    if ($striped) {
        $classes .= ' table-striped';
    }
    if ($hover) {
        $classes .= ' table-hover';
    }
@endphp

@if ($responsive)
    <div class="table-responsive">
@endif

<table {{ $attributes->merge(['class' => $classes]) }}>
    <thead class="table-light">
        {{ $thead }}
    </thead>
    <tbody>
        {{ $slot }}
    </tbody>
</table>

@if ($responsive)
    </div>
@endif
