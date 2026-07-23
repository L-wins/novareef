@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Esto es lo que necesita revisión hoy." />

    <x-dashboard.section label="Resumen disciplinario">
        <div class="stats-grid">
            <x-dashboard.stat-card icon="fa-gavel" color="red" :value="$activasCount" label="Sanciones activas" href="{{ route('sanciones.index') }}" />
            <x-dashboard.stat-card icon="fa-scale-balanced" color="amber" :value="$apelacionesPendientes" label="Apelaciones pendientes" href="{{ route('sanciones.index') }}" />
            <x-dashboard.stat-card icon="fa-file-circle-question" color="blue" :value="$justificacionesPendientesCount" label="Justificaciones pendientes" href="{{ route('sanciones.justificaciones.pendientes') }}" />
        </div>
    </x-dashboard.section>

    <div class="widgets-grid">

        <x-dashboard.widget-card titulo="Sanciones activas recientes" icono="fa-gavel" color="red"
            href="{{ route('sanciones.index') }}" cta="Ver todas">
            @if ($recientes->isEmpty())
                <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay sanciones activas.</span></div>
            @else
                <div class="widget-list">
                    @foreach ($recientes as $s)
                        <a href="{{ route('sanciones.show', $s->idSancion) }}" class="widget-list-item">
                            <div class="widget-list-item__main">
                                <span class="widget-list-item__title">{{ $s->arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $s->idArbitro }}</span>
                                <span class="widget-list-item__meta">{{ $s->tipo->etiqueta ?? '' }} · {{ $s->fechaHecho->format('d/m/Y') }}</span>
                            </div>
                            <span class="dash-pill" data-color="red">Activa</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-dashboard.widget-card>

        <x-dashboard.widget-card titulo="Justificaciones pendientes" icono="fa-file-circle-question" color="blue"
            href="{{ route('sanciones.justificaciones.pendientes') }}" cta="Revisar">
            @if ($justificacionesPendientes->isEmpty())
                <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay justificaciones pendientes de revisión.</span></div>
            @else
                <div class="widget-list">
                    @foreach ($justificacionesPendientes as $j)
                        <div class="widget-list-item">
                            <div class="widget-list-item__main">
                                <span class="widget-list-item__title">{{ $j->arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $j->idArbitro }}</span>
                                <span class="widget-list-item__meta">{{ $j->asistencia->sesion->tema ?? '' }} · vence {{ $j->fechaLimite->format('d/m/Y') }}</span>
                            </div>
                            <span class="dash-pill" data-color="amber">Pendiente</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.widget-card>

    </div>

</div>
@endsection
