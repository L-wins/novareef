@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Partidos finalizados pendientes de calificación." />

    <x-dashboard.section label="Mi resumen">
        <div class="stats-grid">
            <x-dashboard.stat-card
                :class="$partidosPendientesDeCalificar->count() > 0 ? 'stat-card--urgent' : ''"
                icon="fa-star"
                :color="$partidosPendientesDeCalificar->count() > 0 ? 'red' : 'amber'"
                :value="$partidosPendientesDeCalificar->count()"
                label="Pendientes de calificar" />
        </div>
    </x-dashboard.section>

    <x-dashboard.widget-card titulo="Partidos pendientes de calificar" icono="fa-star" color="amber">
        @if ($partidosPendientesDeCalificar->isEmpty())
            <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No tienes partidos pendientes de calificar.</span></div>
        @else
            <div class="widget-list">
                @foreach ($partidosPendientesDeCalificar as $partido)
                    <a href="{{ route('designaciones.calificaciones.index', $partido->idPartido) }}" class="widget-list-item">
                        <div class="widget-list-item__main">
                            <span class="widget-list-item__title">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</span>
                            <span class="widget-list-item__meta">{{ $partido->torneo->nombreTorneo ?? '' }} · {{ $partido->fechaPartido->format('d/m/Y') }}</span>
                        </div>
                        <span class="dash-pill" data-color="amber">Calificar</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-dashboard.widget-card>

</div>
@endsection
