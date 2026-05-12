@props([
    'icon' => '',
    'label' => '',
    'active' => false,
    'href' => '#',
    'menuOpen' => false,
    'color' => '',
])

<li class="nav-item {{ $menuOpen ? 'menu-open' : '' }}">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        {{-- Inyectar la clase de color aquí --}}
        <i class="nav-icon {{ $icon }} {{ $color }}"></i>
        <p>
            {{ $label }}
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        {{ $slot }}
    </ul>
</li>
