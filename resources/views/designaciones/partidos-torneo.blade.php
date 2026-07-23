@extends('layouts.app')

@section('titulo', $torneo->nombreTorneo)
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container desi-shell">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>{{ $torneo->nombreTorneo }}</span>
    </div>

    {{-- ═══ HERO ═══ --}}
    <div class="desi-hero">
        <div class="desi-hero__main">
            <div class="desi-hero__icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div class="desi-hero__left">
                <div class="desi-hero__eyebrow">
                    @if($criticosCount > 0)
                    <span class="desi-alerta-critico">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ $criticosCount }} crítico{{ $criticosCount > 1 ? 's' : '' }}
                    </span>
                    @endif
                    <span class="desi-hero__label">{{ $torneo->temporada }} · {{ ucfirst($torneo->tipoTorneo) }}</span>
                </div>
                <h1 class="desi-hero__title">{{ $torneo->nombreTorneo }}</h1>
                <p class="desi-hero__sub">
                    {{ $partidos->total() }} partido{{ $partidos->total() !== 1 ? 's' : '' }}
                    @if(request()->hasAny(['estado','fecha','division']))
                    · <span class="desi-filtered-label">filtrado{{ $partidos->total() !== 1 ? 's' : '' }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="desi-hero__acciones">
            <button type="button" class="btn btn-ghost desi-action-btn" id="btn-abrir-exportar-pdf">
                <i class="fa-solid fa-file-pdf"></i>
                Exportar PDF
            </button>
            @can('crear-designaciones')
            <a href="{{ route('designaciones.create') }}" class="btn btn-primary desi-action-btn">
                <i class="fa-solid fa-plus"></i>
                Nuevo partido
            </a>
            @endcan
        </div>
    </div>

    <div class="desi-tournament-summary" aria-label="Resumen del torneo">
        <div class="desi-summary-item">
            <span>Partidos listados</span>
            <strong>{{ $partidos->total() }}</strong>
        </div>
        <div class="desi-summary-item {{ $criticosCount > 0 ? 'desi-summary-item--danger' : '' }}">
            <span>Críticos</span>
            <strong>{{ $criticosCount }}</strong>
        </div>
        <div class="desi-summary-item">
            <span>Filtros activos</span>
            <strong>{{ request()->hasAny(['estado','fecha','division']) ? 'Sí' : 'No' }}</strong>
        </div>
    </div>

    {{-- ═══ EXPORTAR PDF: rango de fechas ═══ --}}
    <form method="GET" action="{{ route('designaciones.listado.pdf', ['idTorneo' => $torneo->idTorneo]) }}"
          id="form-exportar-pdf" class="desi-filter-bar desi-filter-bar--pdf" style="display:none;" target="_blank">
        @if(request()->filled('division'))
            <input type="hidden" name="division" value="{{ request('division') }}">
        @endif

        <div class="desi-filter-item">
            <label class="desi-filter-label">Desde</label>
            <input type="text" name="desde" class="form-input desi-date-input" data-nova-date placeholder="dd/mm/aaaa">
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Hasta</label>
            <input type="text" name="hasta" class="form-input desi-date-input" data-nova-date placeholder="dd/mm/aaaa">
        </div>

        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-file-pdf"></i> Generar PDF
            </button>
            <p class="field-hint desi-filter-hint">Deja los campos vacíos para exportar todo el torneo.</p>
        </div>
    </form>

    {{-- ═══ FILTROS ═══ --}}
    <form method="GET" action="{{ route('designaciones.index') }}" class="desi-filter-bar" data-auto-filter>
        <input type="hidden" name="torneo" value="{{ $torneo->idTorneo }}">

        <div class="desi-filter-item">
            <label class="desi-filter-label">Estado</label>
            <select name="estado" class="filter-select" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach(['borrador'=>'Borrador','programado'=>'Programado','confirmado'=>'Confirmado','critico'=>'Crítico','aplazado'=>'Aplazado','finalizado'=>'Finalizado','cancelado'=>'Cancelado'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('estado') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Fecha</label>
            <input type="text" name="fecha" class="form-input" data-nova-date
                   value="{{ request('fecha') }}" placeholder="dd/mm/aaaa">
        </div>

        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            @if(request()->hasAny(['estado','fecha','division']))
            <a href="{{ route('designaciones.index', ['torneo' => $torneo->idTorneo]) }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Limpiar
            </a>
            @endif
        </div>
    </form>

    {{-- ═══ LISTA DE PARTIDOS ═══ --}}
    <div class="desi-list-toolbar">
        <div>
            <span class="desi-list-toolbar__label">Partidos</span>
            <strong>{{ $partidos->total() }} resultado{{ $partidos->total() !== 1 ? 's' : '' }}</strong>
        </div>
    </div>

    <div class="desi-list">
        @php $fechaDivisorAnterior = null; @endphp
        @forelse($partidos as $partido)
        @php
            $fechaDelPartido = $partido->fechaPartido?->format('Y-m-d');
        @endphp
        @if($fechaDelPartido !== $fechaDivisorAnterior)
        @php
            // Etiqueta inicial calculada en servidor — evita el parpadeo vacío
            // antes de que date-divider.js la recalcule (y la mantenga viva).
            $labelDivisor = match(true) {
                $partido->fechaPartido?->isToday()    => 'Hoy',
                $partido->fechaPartido?->isTomorrow() => 'Mañana',
                $partido->fechaPartido?->isYesterday()=> 'Ayer',
                default => ucfirst($partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM') ?? ''),
            };
        @endphp
        <div class="date-divider" data-fecha="{{ $fechaDelPartido }}">
            <span class="date-divider__label">{{ $labelDivisor }}</span>
            <span class="date-divider__line"></span>
        </div>
        @php $fechaDivisorAnterior = $fechaDelPartido; @endphp
        @endif
        @php
            $estado     = $partido->estadoPartido;
            $esCritico  = $estado === 'critico';
            $esTerminal = in_array($estado, ['finalizado','cancelado']);
            $fechaHuman = $partido->fechaPartido?->locale('es')->isoFormat('ddd D [de] MMMM');
            $esHoy      = $partido->fechaPartido?->isToday();
            $esMañana   = $partido->fechaPartido?->isTomorrow();

            $estadoLabel = ['borrador'=>'Borrador','programado'=>'Programado','confirmado'=>'Confirmado',
                            'critico'=>'Crítico','aplazado'=>'Aplazado',
                            'finalizado'=>'Finalizado','cancelado'=>'Cancelado'][$estado] ?? $estado;

            $totalRoles = $partido->formato?->maxArbitros ?? 0;
            $confirmados = $partido->designaciones->where('estadoDesignacion','confirmada')->count();
            $pendientes  = $partido->designaciones->where('estadoDesignacion','pendiente')->count();
            $pct         = $totalRoles > 0 ? round(($confirmados / $totalRoles) * 100) : 0;
        @endphp

        <div class="desi-card desi-card--{{ $estado }} {{ $esCritico ? 'desi-card--critico' : '' }}"
             data-partido="{{ $partido->idPartido }}">

            {{-- Accent bar izquierdo --}}
            <div class="desi-card__accent"></div>

            {{-- Contenido --}}
            <div class="desi-card__body">

                {{-- Fila superior: estado + fecha --}}
                <div class="desi-card__toprow">
                    <div class="desi-card__badges">
                        <span class="desi-estado-pill desi-estado--{{ $estado }}">
                            @if($esCritico)
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            @endif
                            {{ $estadoLabel }}
                        </span>

                        {{-- data-fecha: date-divider.js recalcula esto cada minuto para que
                             no quede contradiciendo al divisor sticky si la página sigue
                             abierta después de medianoche. --}}
                        <span class="desi-fecha-badge {{ $esHoy ? 'desi-hoy-badge' : ($esMañana ? 'desi-manana-badge' : '') }}"
                              data-fecha="{{ $fechaDelPartido }}"
                              @if(!$esHoy && !$esMañana) style="display:none" @endif>
                            @if($esHoy)
                                <i class="fa-solid fa-circle desi-dot-icon"></i> HOY
                            @elseif($esMañana)
                                <i class="fa-solid fa-sun desi-sun-icon"></i> Mañana
                            @endif
                        </span>

                        @if($partido->torneo?->tipoTorneo === 'oficial')
                        <span class="desi-oficial-badge"><i class="fa-solid fa-shield-halved"></i> Oficial</span>
                        @endif
                    </div>

                    <div class="desi-card__fecha">
                        <i class="fa-regular fa-calendar"></i>
                        {{ ucfirst($fechaHuman) }}
                        <span class="desi-hora">{{ substr($partido->horaPartido, 0, 5) }}</span>
                    </div>
                </div>

                {{-- Match --}}
                <div class="desi-card__match">
                    <span class="desi-equipo">{{ $partido->equipoLocal }}</span>
                    <span class="desi-vs">vs</span>
                    <span class="desi-equipo">{{ $partido->equipoVisitante }}</span>
                </div>

                {{-- Meta row --}}
                <div class="desi-card__meta">
                    @if($partido->division)
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-layer-group"></i>
                        {{ $partido->division->nombreDivision }}
                    </span>
                    @endif
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $partido->sede?->nombreSede ?? 'Sin sede' }}
                        @if($partido->sede?->ciudad) · {{ $partido->sede->ciudad }}@endif
                    </span>
                    @if($partido->modalidadPago)
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-coins"></i>
                        {{ ucfirst($partido->modalidadPago) }}
                    </span>
                    @endif
                </div>

                {{-- Árbitros + progreso --}}
                <div class="desi-card__bottom">
                    <div class="desi-arbitros-row">
                        @forelse($partido->designaciones->sortBy('rol.orden') as $d)
                        <span class="desi-arbitro-chip desi-arbitro-chip--{{ $d->estadoDesignacion }}">
                            <span class="desi-arbitro-avatar">
                                {{ strtoupper(substr($d->arbitro?->usuario?->nombreUsuario ?? 'A', 0, 1)) }}
                            </span>
                            <span class="desi-arbitro-info">
                                <span class="desi-arbitro-rol">{{ $d->rol?->nombre }}</span>
                                <span class="desi-arbitro-nombre">{{ $d->arbitro?->usuario?->nombreUsuario ?? '—' }}</span>
                            </span>
                            <span class="desi-chip-estado">
                                @if($d->estaConfirmada()) <i class="fa-solid fa-check"></i>
                                @elseif($d->estaRechazada()) <i class="fa-solid fa-xmark"></i>
                                @else <i class="fa-regular fa-clock"></i>
                                @endif
                            </span>
                        </span>
                        @empty
                        <span class="desi-sin-arbitros">
                            <i class="fa-solid fa-user-slash"></i>
                            Sin árbitros asignados
                        </span>
                        @endforelse
                    </div>

                    {{-- Barra de progreso --}}
                    @if($totalRoles > 0 && !$esTerminal)
                    <div class="desi-progress-wrap">
                        <div class="desi-progress-bar">
                            <div class="desi-progress-fill" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="desi-progress-label">{{ $confirmados }}/{{ $totalRoles }}</span>
                    </div>
                    @endif
                </div>

            </div>

            {{-- CTA --}}
            <div class="desi-card__cta">
                <a href="{{ route('designaciones.show', $partido->idPartido) }}"
                   class="desi-gestionar-btn"
                   title="Gestionar partido">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Gestionar</span>
                </a>
            </div>

        </div>
        @empty

        <div class="empty-state empty-state--designaciones">
            <span class="empty-state__icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </span>
            <p class="empty-state__title">No hay partidos en este torneo</p>
            <p class="empty-state__sub">
                @can('crear-designaciones')
                <a href="{{ route('designaciones.create') }}" class="btn btn-primary empty-state__action">
                    <i class="fa-solid fa-plus"></i> Crear primer partido
                </a>
                @else
                Cuando el designador cree partidos aparecerán aquí.
                @endcan
            </p>
        </div>

        @endforelse
    </div>

    {{-- Paginación --}}
    @if($partidos->hasPages())
    <div class="pagination-wrap">
        {{ $partidos->links() }}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
window.colegioId = {{ Auth::user()->idColegio }};
window.broadcastAuthEndpoint = "{{ url('/broadcasting/auth') }}";
</script>
@vite(['resources/js/designaciones/designaciones.js'])
@endpush
