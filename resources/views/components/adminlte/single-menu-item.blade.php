@props([
    'href' => '#',
    'icon' => '',
    'label' => '',
    'active' => false,
    'color' => '',
])

<li class="nav-item">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        {{-- Se agrega la variable $color a la clase del icono --}}
        <i class="nav-icon {{ $icon }} {{ $color }}"></i>
        <p>{{ $label }}</p>
    </a>
</li>
