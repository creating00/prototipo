@extends('layouts.app')

@section('page-title', 'Ejemplo')

@section('content')
    <div class="row">
        @foreach (config('dashboardCards.cards') as $card)
            <div class="col-md-3 col-sm-6">
                <x-admin-lte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :svgPath="$card['svgPath'] ?? ''"
                    :icon="$card['icon'] ?? ''" :viewBox="$card['viewBox']" :url="$card['url']" :description="$card['description'] ?? null" :customBgColor="$card['customBgColor'] ?? null" />
            </div>
        @endforeach
    </div>
@endsection
