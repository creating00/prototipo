<svg class="small-box-icon" fill="currentColor" viewBox="{{ $svgViewBox }}" xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true">
    @if ($useFullPath)
        {!! $svgContent !!}
    @else
        <path d="{{ $svgContent }}"></path>
    @endif
</svg>
