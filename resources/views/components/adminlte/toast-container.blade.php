<div {{ $attributes->merge(['class' => "toast-container position-fixed {$position} {$class}"]) }}>
    {{ $slot }}
</div>
