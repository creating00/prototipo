@props(['id', 'height' => 300])

<div class="chart-container position-relative" style="height: {{ $height }}px;">
    <canvas id="{{ $id }}"></canvas>
</div>
