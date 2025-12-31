<div class="card card-{{ $type }} card-outline mb-4">
    @if ($title || isset($tools) || $showTools)
        <div class="card-header">
            @if ($title)
                <h3 class="card-title text-truncate" style="max-width: 80%;">
                    {{ $title }}
                </h3>
            @endif

            <div class="card-tools">
                @if (isset($tools))
                    {{ $tools }}
                @elseif ($showTools)
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                        <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                        <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                    {{-- <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
                        <i class="bi bi-x-lg"></i>
                    </button> --}}
                @endif
            </div>
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
