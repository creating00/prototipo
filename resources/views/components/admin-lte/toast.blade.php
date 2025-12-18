<div
    {{ $attributes->merge([
        'id' => $id,
        'class' => 'toast' . ($color ? " toast-{$color}" : '') . ($class ? " {$class}" : ''),
        'role' => 'alert',
        'aria-live' => 'assertive',
        'aria-atomic' => 'true',
        'data-bs-autohide' => $autohide ? 'true' : 'false',
        'data-bs-delay' => $delay,
    ]) }}>
    <div class="toast-header">
        @if ($icon)
            <i class="{{ $icon }} me-2"></i>
        @endif
        <strong class="me-auto">{{ $title ?? 'Bootstrap' }}</strong>
        <small>{{ $time }}</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        {{ $slot }}
    </div>
</div>
