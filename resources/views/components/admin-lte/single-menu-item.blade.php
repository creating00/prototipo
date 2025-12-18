@props([
    'href' => '#',
    'icon' => '',
    'label' => '',
    'active' => false,
])

<li class="nav-item">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }}"></i>
        <p>{{ $label }}</p>
    </a>
</li>
