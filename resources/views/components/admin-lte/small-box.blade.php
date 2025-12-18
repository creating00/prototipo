<div class="small-box {{ $bgColorClass }}"
    @if ($customBgColor) style="background-color: {{ $customBgColor }} !important; color: white !important;" @endif>
    <div class="inner">
        <h3>{{ $value }}</h3>
        <p>{{ $title }}</p>
        @if ($description)
            <small class="text-opacity-75">{{ $description }}</small>
        @endif
    </div>
    <x-admin-lte.small-box-icon :svgViewBox="$svgViewBox" :svgContent="$svgContent" :useFullPath="$useFullPath" />

    <x-admin-lte.small-box-footer :url="$url" />
</div>
