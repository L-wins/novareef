@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="container">

    <x-dashboard.welcome subtitulo="Aquí tienes el panorama completo de tu colegio." />

    <x-dashboard.modules-grid compact :modulos="$modulos" :modulos-plan="$modulosPlan ?? []" />

    @if ($partidosNominaSinGenerar > 0)
        @include('partials.alerta-banner', [
            'nivel'  => 'critico',
            'titulo' => 'Hay partidos finalizados sin nómina generada',
            'texto'  => "{$partidosNominaSinGenerar} partido" . ($partidosNominaSinGenerar === 1 ? '' : 's') . " finalizado" . ($partidosNominaSinGenerar === 1 ? '' : 's') . " en modalidad nómina no generó pago a los árbitros — revisa que las tarifas de división/rol/formato estén configuradas y que las designaciones hayan sido confirmadas.",
            'href'   => route('finanzas.balance.index'),
        ])
    @endif

    <x-dashboard.section label="Resumen">
        <div class="stats-grid">
            <x-dashboard.stat-card icon="fa-users" color="teal" :value="$arbitrosActivos" label="Árbitros activos"
                href="{{ route('arbitros.index') }}" :sub="$arbitrosProceso > 0 ? $arbitrosProceso . ' en proceso de ingreso' : null" />
            <x-dashboard.stat-card class="stat-card--hero" icon="fa-sack-dollar" color="emerald" :value="'$' . number_format($bolsillos['disponibleReal'], 0, ',', '.')" label="Disponible real"
                href="{{ route('finanzas.balance.index') }}" sub="Caja menos lo que aún falta pagar" />
            <x-dashboard.stat-card :class="$designaciones['criticosCount'] > 0 ? 'stat-card--urgent' : ''" icon="fa-triangle-exclamation" color="red" :value="$designaciones['criticosCount']" label="Partidos críticos"
                href="{{ route('designaciones.index') }}" />
            <x-dashboard.stat-card icon="fa-gavel" color="amber" :value="$sanciones['activasCount']" label="Sanciones activas"
                href="{{ route('sanciones.index') }}" />
        </div>
    </x-dashboard.section>

    <x-dashboard.section label="Necesita tu atención">
        <div class="widgets-grid">

            <x-dashboard.widget-card titulo="Designaciones" icono="fa-clipboard-list" color="emerald"
                href="{{ route('designaciones.index') }}" cta="Ver todas">
                <x-slot:meta>{{ $designaciones['hoyCount'] }} partido{{ $designaciones['hoyCount'] === 1 ? '' : 's' }} hoy</x-slot:meta>
                @if ($designaciones['pendientesConfirmacion']->isEmpty())
                    <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay designaciones pendientes de confirmación.</span></div>
                @else
                    <div class="widget-list">
                        @foreach ($designaciones['pendientesConfirmacion'] as $d)
                            <div class="widget-list-item">
                                <div class="widget-list-item__main">
                                    <span class="widget-list-item__title">{{ $d->arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $d->idArbitro }} — {{ $d->rol->nombre ?? '' }}</span>
                                    <span class="widget-list-item__meta">{{ $d->partido->torneo->nombreTorneo ?? '' }} · {{ $d->partido->fechaPartido->format('d/m/Y') }}</span>
                                </div>
                                <span class="dash-pill" data-color="amber">Pendiente</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.widget-card>

            <x-dashboard.widget-card titulo="Sanciones" icono="fa-gavel" color="red"
                href="{{ route('sanciones.index') }}" cta="Ver todas">
                <x-slot:meta>{{ $sanciones['apelacionesPendientes'] }} apelaci{{ $sanciones['apelacionesPendientes'] === 1 ? 'ón' : 'ones' }} pendiente{{ $sanciones['apelacionesPendientes'] === 1 ? '' : 's' }}</x-slot:meta>
                @if ($sanciones['recientes']->isEmpty())
                    <div class="widget-empty"><i class="fa-solid fa-circle-check"></i><span>No hay sanciones activas.</span></div>
                @else
                    <div class="widget-list">
                        @foreach ($sanciones['recientes'] as $s)
                            <div class="widget-list-item">
                                <div class="widget-list-item__main">
                                    <span class="widget-list-item__title">{{ $s->arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $s->idArbitro }}</span>
                                    <span class="widget-list-item__meta">{{ $s->tipo->etiqueta ?? $s->tipo->nombre ?? '' }} · {{ $s->fechaHecho->format('d/m/Y') }}</span>
                                </div>
                                <span class="dash-pill" data-color="red">Activa</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.widget-card>

            <x-dashboard.widget-card titulo="Académico" icono="fa-graduation-cap" color="purple"
                href="{{ route('academico.sesiones.index') }}" cta="Ver todas">
                <x-slot:meta>{{ $sesionesProximas->count() }} sesi{{ $sesionesProximas->count() === 1 ? 'ón' : 'ones' }} próxima{{ $sesionesProximas->count() === 1 ? '' : 's' }}</x-slot:meta>
                @if ($sesionesProximas->isEmpty())
                    <div class="widget-empty"><i class="fa-solid fa-calendar-xmark"></i><span>No hay sesiones próximas programadas.</span></div>
                @else
                    <div class="widget-list">
                        @foreach ($sesionesProximas as $s)
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
    </x-dashboard.section>

    <x-dashboard.section label="Uso de tu plan">
        @php($colorUso = $limiteArbitrosPorcentaje >= 100 ? 'red' : ($limiteArbitrosPorcentaje >= 80 ? 'yellow' : 'green'))
        <div class="plan-usage">
            <div class="plan-usage-head">
                <span class="plan-usage-label">
                    <i class="fa-solid fa-users"></i>
                    Árbitros
                </span>
                <span class="plan-usage-value" data-color="{{ $limiteArbitros === null ? 'green' : $colorUso }}">
                    {{ $limiteArbitrosUsados }} {{ $limiteArbitros === null ? '(ilimitado)' : "/ {$limiteArbitros}" }}
                </span>
            </div>
            @if ($limiteArbitros !== null)
                <div class="plan-usage-bar">
                    <div class="plan-usage-fill" data-color="{{ $colorUso }}"
                         style="width: {{ min($limiteArbitrosPorcentaje, 100) }}%;"></div>
                </div>
            @endif
        </div>

        @include('partials.limite-plan-banner', [
            'recurso'    => 'árbitros',
            'usados'     => $limiteArbitrosUsados,
            'limite'     => $limiteArbitros,
            'porcentaje' => $limiteArbitrosPorcentaje,
        ])
    </x-dashboard.section>

</div>
@endsection
