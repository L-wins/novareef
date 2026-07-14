@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Aquí tienes tu resumen personal." />

    <x-dashboard.section label="Mi resumen">
        <div class="stats-grid">
            <x-dashboard.stat-card class="stat-card--hero" icon="fa-sack-dollar" color="amber" :value="'$' . number_format($saldoPendienteCobrar, 0, ',', '.')" label="Pendiente por cobrar" href="{{ route('arbitros.estado-cuenta') }}" />
            <x-dashboard.stat-card icon="fa-futbol" color="emerald" :value="$proximosPartidos->count()" label="Próximos partidos" href="{{ route('mis-partidos.index') }}" />
            <x-dashboard.stat-card icon="fa-graduation-cap" color="purple" :value="$proximasClases->count()" label="Próximas clases" href="{{ route('academico.mis-clases') }}" />
            @if ($porcentajeAsistencia !== null)
                <x-dashboard.stat-card icon="fa-chart-line" color="blue" :value="number_format($porcentajeAsistencia, 0) . '%'" label="Asistencia académica" href="{{ route('academico.mis-clases') }}" />
            @endif
        </div>
    </x-dashboard.section>

    @unless ($yaReportoDisponibilidad)
        <div class="plan-limite-banner plan-limite-banner--advertencia">
            <div class="plan-limite-banner__icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="plan-limite-banner__body">
                <p class="plan-limite-banner__title">Aún no reportaste tu disponibilidad de esta semana</p>
                <p class="plan-limite-banner__text">Repórtala para que el designador pueda contar contigo.</p>
            </div>
            <a href="{{ route('disponibilidad.index') }}" class="btn btn-primary plan-limite-banner__cta">Reportar ahora</a>
        </div>
    @endunless

    <div class="widgets-grid">

        <x-dashboard.widget-card titulo="Próximos partidos" icono="fa-futbol" color="emerald"
            href="{{ route('mis-partidos.index') }}" cta="Ver todos">
            @if ($proximosPartidos->isEmpty())
                <div class="widget-empty"><i class="fa-solid fa-futbol"></i><span>No tienes partidos próximos.</span></div>
            @else
                <div class="widget-list">
                    @foreach ($proximosPartidos as $d)
                        <div class="widget-list-item">
                            <div class="widget-list-item__main">
                                <span class="widget-list-item__title">{{ $d->partido->equipoLocal }} vs {{ $d->partido->equipoVisitante }}</span>
                                <span class="widget-list-item__meta">{{ $d->partido->torneo->nombreTorneo ?? '' }} · {{ $d->rol->nombre ?? '' }} · {{ $d->partido->fechaPartido->format('d/m/Y') }}</span>
                            </div>
                            <span class="dash-pill" data-color="{{ $d->estadoDesignacion === 'confirmada' ? 'blue' : 'amber' }}">
                                {{ $d->estadoDesignacion === 'confirmada' ? 'Confirmado' : 'Por confirmar' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.widget-card>

        <x-dashboard.widget-card titulo="Próximas clases" icono="fa-graduation-cap" color="purple"
            href="{{ route('academico.mis-clases') }}" cta="Ver todas">
            @if ($proximasClases->isEmpty())
                <div class="widget-empty"><i class="fa-solid fa-graduation-cap"></i><span>No tienes clases próximas.</span></div>
            @else
                <div class="widget-list">
                    @foreach ($proximasClases as $s)
                        <div class="widget-list-item">
                            <div class="widget-list-item__main">
                                <span class="widget-list-item__title">{{ $s->tema }}</span>
                                <span class="widget-list-item__meta">{{ $s->tipo->etiqueta ?? '' }} · {{ $s->fechaSesion->format('d/m/Y') }}</span>
                            </div>
                            <span class="dash-pill" data-color="blue">{{ $s->estadoSesion === 'en_curso' ? 'En curso' : 'Programada' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-dashboard.widget-card>

    </div>

</div>
@endsection
