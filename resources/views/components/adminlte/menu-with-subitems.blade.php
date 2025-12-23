@props([
    'icon' => '',
    'label' => '',
    'active' => false,
    'menuOpen' => false,
    'badge' => null,
    'badgeClass' => 'text-bg-secondary',
])

<li class="nav-item {{ $menuOpen ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $active ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }}"></i>
        <p>
            {{ $label }}
            @if ($badge)
                <span class="nav-badge badge {{ $badgeClass }} me-3">{{ $badge }}</span>
            @endif
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        {{ $slot }}
    </ul>
</li>
