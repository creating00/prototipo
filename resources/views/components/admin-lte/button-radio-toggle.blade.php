@props(['id', 'name', 'color' => 'primary', 'checked' => false, 'value'])

<input type="radio" class="btn-check" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
    autocomplete="off" @if ($checked) checked @endif {{ $attributes }} {{-- Para aceptar atributos adicionales como 'disabled' --}} />
<label class="btn btn-outline-{{ $color }}" for="{{ $id }}">
    {{ $slot }}
</label>
