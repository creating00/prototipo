@props([
    'href' => '#',
    'icon' => '',
    'label' => '',
    'active' => false,
    'color' => '',
    'badge' => null,
])

<li class="nav-item">
    <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }} {{ $color }}"></i>
        <p>
            {{ $label }}
            @if($badge)
                <span class="right {{ $badge['class'] ?? 'badge text-bg-info' }}">
                    {{ $badge['value'] }}
                </span>
            @endif
        </p>
    </a>
</li>
