@props([
    'active' => false,
    'hasSubmenu' => false,
    'menuOpen' => false,
])

<li {{ $attributes->class(['nav-item', 'menu-open' => $menuOpen]) }}>
    {{ $slot }}
</li>
