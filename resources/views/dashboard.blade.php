@extends('layouts.app')

@section('page-title', 'Panel de Control')

@section('content')
    <div class="row">
        {{-- Usamos $cards que viene del controlador --}}
        
        @foreach ($cards as $card)
            <div class="col-md-3 col-sm-6">
                <x-adminlte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :icon="$card['icon'] ?? ''"
                    :viewBox="$card['viewBox']" :url="route($card['route']['href'])" :footerLabel="$card['route']['label']" :description="$card['description'] ?? null" :customBgColor="$card['customBgColor'] ?? null" />
            </div>
        @endforeach
    </div>
@endsection
