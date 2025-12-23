<div class="card card-{{ $type }} card-outline mb-4">
    @if ($title)
        <div class="card-header">
            <div class="card-title">{{ $title }}</div>
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
