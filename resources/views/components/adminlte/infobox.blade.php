<div class="info-box h-100">
    <span class="info-box-icon text-bg-{{ $color }} shadow-sm">
        <i class="{{ $icon }}"></i>
    </span>

    <div class="info-box-content">
        <span class="info-box-text fw-semibold text-uppercase small">
            {{ $text }}
        </span>

        <div class="d-flex justify-content-between align-items-end">
            <span class="info-box-number fw-bold fs-4">
                @if ($prefix)
                    <small class="fs-6">{{ $prefix }}</small>
                    {{ number_format($number, 2) }}
                @else
                    {{ number_format($number) }}
                @endif

                @if ($suffix ?? null)
                    <small class="fs-6">{{ $suffix }}</small>
                @endif
            </span>

            @if ($secondaryNumber !== null)
                <span class="text-muted small text-end">
                    {{ number_format($secondaryNumber, 2) }}
                    @if ($secondarySuffix)
                        {{ $secondarySuffix }}
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>
