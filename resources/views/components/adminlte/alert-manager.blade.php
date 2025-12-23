@props([
    'autoClose' => true,
    'dismissible' => true,
])

@php
    $alertConfig = [
        'success' => [
            'type' => 'success',
            'autoClose' => 3000,
            'icon' => 'fas fa-check-circle',
        ],
        'error' => [
            'type' => 'danger',
            'autoClose' => 8000,
            'icon' => 'fas fa-exclamation-circle',
        ],
        'warning' => [
            'type' => 'warning',
            'autoClose' => 6000,
            'icon' => 'fas fa-exclamation-triangle',
        ],
        'info' => [
            'type' => 'info',
            'autoClose' => 4000,
            'icon' => 'fas fa-info-circle',
        ],
    ];
@endphp

@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session($type))
        <x-admin-lte.alert :type="$alertConfig[$type]['type']" :message="session($type)" :dismissible="$dismissible" :autoClose="$autoClose ? $alertConfig[$type]['autoClose'] : false" :icon="$alertConfig[$type]['icon']" />
    @endif
@endforeach

@if ($errors->any() && session('show_validation_errors', true))
    <x-admin-lte.alert type="danger" :dismissible="$dismissible" :autoClose="$autoClose ? 10000 : false" icon="fas fa-times-circle">
        <strong>Errores de validación:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-admin-lte.alert>
@endif
