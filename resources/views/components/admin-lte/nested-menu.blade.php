{{-- resources/views/components/admin-lte/nested-menu.blade.php --}}
@props([
    'icon' => '',
    'label' => '',
    'active' => false,
    'href' => '#', // Agregar href opcional
    'menuOpen' => false, // Agregar soporte para menu-open
])

<li class="nav-item {{ $menuOpen ? 'menu-open' : '' }}">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }}"></i>
        <p>
            {{ $label }}
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        {{ $slot }}
    </ul>
</li>
