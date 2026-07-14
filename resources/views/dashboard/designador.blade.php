@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Esto es lo que necesita tu atención hoy." />

    <x-dashboard.section label="Resumen de designaciones">
        <div class="stats-grid">
            <x-dashboard.stat-card :class="$criticosCount > 0 ? 'stat-card--urgent' : ''" icon="fa-triangle-exclamation" color="red" :value="$criticosCount" label="Partidos críticos" href="{{ route('designaciones.index') }}" sub="Sin todos sus roles cubiertos" />
            <x-dashboard.stat-card icon="fa-calendar-day" color="blue" :value="$hoyCount" label="Partidos hoy" href="{{ route('designaciones.index') }}" />
        </div>
    </x-dashboard.section>

    <x-dashboard.section label="Acciones frecuentes">
        <div class="modules-grid">
            <a href="{{ route('designaciones.create') }}" class="module-card module-card--link">
                <div class="mod-icon-box ic-emerald"><i class="fa-solid fa-plus"></i></div>
                <div class="mod-info">
                    <div class="mod-name">Crear partido</div>
                    <div class="mod-desc">Nuevo partido en borrador</div>
                </div>
            </a>
            <a href="{{ route('disponibilidad.general') }}" class="module-card module-card--link">
                <div class="mod-icon-box ic-teal"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="mod-info">
                    <div class="mod-name">Disponibilidad</div>
                    <div class="mod-desc">Ver disponibilidad de los árbitros</div>
                </div>
            </a>
        </div>
    </x-dashboard.section>

    <x-dashboard.widget-card titulo="Designaciones pendientes de confirmación" icono="fa-clipboard-list" color="amber"
        href="{{ route('designaciones.index') }}" cta="Ver todas">
        @if ($pendientesConfirmacion->isEmpty())
            <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay designaciones pendientes de confirmación.</span></div>
        @else
            <div class="widget-list">
                @foreach ($pendientesConfirmacion as $d)
                    <a href="{{ route('designaciones.show', $d->idPartido) }}" class="widget-list-item">
                        <div class="widget-list-item__main">
                            <span class="widget-list-item__title">{{ $d->arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $d->idArbitro }} — {{ $d->rol->nombre ?? '' }}</span>
                            <span class="widget-list-item__meta">{{ $d->partido->torneo->nombreTorneo ?? '' }} · {{ $d->partido->equipoLocal }} vs {{ $d->partido->equipoVisitante }} · {{ $d->partido->fechaPartido->format('d/m/Y') }}</span>
                        </div>
                        <span class="dash-pill" data-color="amber">Pendiente</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-dashboard.widget-card>

</div>
@endsection
