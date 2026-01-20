@props([
    'href' => '#',
    'icon' => 'bi bi-circle',
    'label' => '',
    'active' => false,
    'color' => '',])

<li class="nav-item">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }} {{ $color }}"></i>
        <p>{{ $label }}</p>
    </a>
</li>
