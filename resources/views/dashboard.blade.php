@extends('layouts.app')

@section('page-title', 'Panel de Control')

@section('content')
    <div class="row">
        {{-- Usamos $cards que viene del controlador --}}

        {{-- @foreach ($cards as $card)
            <div class="col-md-3 col-sm-6">
                <x-adminlte.small-box :title="$card['title']" :value="$card['value']" :color="$card['color']" :icon="$card['icon'] ?? ''"
                    :viewBox="$card['viewBox']" :url="route($card['route']['href'])" :footerLabel="$card['route']['label']" :description="$card['description'] ?? null" :customBgColor="$card['customBgColor'] ?? null" />
            </div>
        @endforeach --}}
        <div class="col-12">
            <div class="text-center py-5">
                <h1 class="sistema-gestion mb-2">
                    Sistema de Gestión
                </h1>
                <h2 class="tecnonauta mb-3">
                    TECNONAUTA
                </h2>
                <p class="subtitulo-servicio">
                    Servicio Técnico
                </p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .sistema-gestion {
            font-family: 'Arial Black', 'Arial Bold', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            color: #2c3e50;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .tecnonauta {
            font-family: 'Impact', 'Arial Black', sans-serif;
            font-size: 5rem;
            font-weight: 900;
            letter-spacing: 5px;
            text-transform: uppercase;
            background: linear-gradient(to bottom,
                    #e0e0e0 0%,
                    #c8c8c8 20%,
                    #a8a8a8 40%,
                    #909090 60%,
                    #787878 80%,
                    #606060 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: brightness(1.1) contrast(1.2);
            text-shadow:
                1px 1px 2px rgba(255, 255, 255, 0.5),
                2px 2px 4px rgba(0, 0, 0, 0.4),
                3px 3px 0px #a0a0a0,
                5px 5px 0px #888888,
                7px 7px 0px #707070,
                9px 9px 20px rgba(0, 0, 0, 0.5);
            -webkit-text-stroke: 2px #909090;
            paint-order: stroke fill;
        }

        .subtitulo-servicio {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.5rem;
            font-weight: 400;
            color: #34495e;
            letter-spacing: 4px;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
        }

        .subtitulo-servicio::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 2px;
            background: linear-gradient(to right, transparent, #95a5a6, transparent);
        }

        @media (max-width: 768px) {
            .sistema-gestion {
                font-size: 2rem;
            }

            .tecnonauta {
                font-size: 3rem;
            }

            .subtitulo-servicio {
                font-size: 1.2rem;
            }
        }
    </style>
@endpush
