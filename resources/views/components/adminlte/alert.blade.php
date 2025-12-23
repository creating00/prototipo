<div class="alert alert-{{ $type }} {{ $dismissible ? 'alert-dismissible fade show' : '' }}" role="alert"
    @if ($autoClose) data-auto-close="{{ $autoClose }}" @endif>

    @if ($message)
        {!! $message !!}
        @if ($link && $linkText)
            <a href="{{ $link }}" class="alert-link">{{ $linkText }}</a>
        @endif
    @else
        {{ $slot }}
        @if ($link && $linkText)
            <a href="{{ $link }}" class="alert-link">{{ $linkText }}</a>
        @endif
    @endif

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
