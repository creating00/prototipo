<div class="card card-primary card-outline mb-4" x-data="{ submitting: false }">
    @if ($title)
        <div class="card-header">
            <div class="card-title">{{ $title }}</div>
        </div>
    @endif

    <form action="{{ $action }}" method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" {{ $attributes }}
        novalidate x-on:submit="submitting = true" x-bind:class="{ 'opacity-50': submitting }">
        @if (strtoupper($method) !== 'GET')
            @csrf
            @if (!in_array(strtoupper($method), ['POST', 'GET']))
                @method($method)
            @endif
        @endif

        <div class="card-body">
            {{ $slot }}
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary" x-bind:disabled="submitting">
                <span x-show="!submitting">{{ $submitText }}</span>
                <span x-show="submitting" x-cloak>
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    {{ $submittingText }}
                </span>
            </button>
        </div>
    </form>
</div>
