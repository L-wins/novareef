@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Esto es lo que tienes programado en Académico." />

    <x-dashboard.section label="Resumen académico">
        <div class="stats-grid">
            <x-dashboard.stat-card icon="fa-graduation-cap" color="purple" :value="$sesionesProximas->count()" label="Sesiones próximas" href="{{ route('academico.sesiones.index') }}" />
            <x-dashboard.stat-card icon="fa-door-open" color="emerald" :value="$sesionesAbiertasAhoraCount" label="Sesiones abiertas ahora" href="{{ route('academico.sesiones.index') }}" />
            <x-dashboard.stat-card icon="fa-file-circle-question" color="blue" :value="$justificacionesPendientesCount" label="Justificaciones pendientes" href="{{ route('sanciones.justificaciones.pendientes') }}" />
        </div>
    </x-dashboard.section>

    <x-dashboard.section label="Acciones frecuentes">
        <div class="modules-grid">
            <a href="{{ route('academico.sesiones.create') }}" class="module-card module-card--link">
                <div class="mod-icon-box ic-purple"><i class="fa-solid fa-plus"></i></div>
                <div class="mod-info">
                    <div class="mod-name">Crear sesión</div>
                    <div class="mod-desc">Nueva sesión académica</div>
                </div>
            </a>
        </div>
    </x-dashboard.section>

    <x-dashboard.widget-card titulo="Próximas sesiones" icono="fa-graduation-cap" color="purple"
        href="{{ route('academico.sesiones.index') }}" cta="Ver todas">
        @if ($sesionesProximas->isEmpty())
            <div class="widget-empty"><i class="fa-solid fa-calendar-xmark"></i><span>No hay sesiones próximas programadas.</span></div>
        @else
            <div class="widget-list">
                @foreach ($sesionesProximas as $s)
                    <a href="{{ route('academico.sesiones.show', $s->idSesion) }}" class="widget-list-item">
                        <div class="widget-list-item__main">
                            <span class="widget-list-item__title">{{ $s->tema }}</span>
                            <span class="widget-list-item__meta">{{ $s->tipo->etiqueta ?? '' }} · {{ $s->fechaSesion->format('d/m/Y') }}</span>
                        </div>
                        <span class="dash-pill" data-color="blue">{{ $s->estadoSesion === 'en_curso' ? 'En curso' : 'Programada' }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-dashboard.widget-card>

</div>
@endsection
