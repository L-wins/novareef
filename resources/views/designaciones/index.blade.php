@extends('layouts.app')

@section('titulo', 'Designaciones')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- ═══ HERO ═══ --}}
    <div class="desi-hero">
        <div class="desi-hero__left">
            <div class="desi-hero__eyebrow">
                @if($criticosCount > 0)
                <span class="desi-alerta-critico">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ $criticosCount }} crítico{{ $criticosCount > 1 ? 's' : '' }}
                </span>
                @endif
                <span class="desi-hero__label">Gestión de partidos</span>
            </div>
            <h1 class="desi-hero__title">Designaciones</h1>
            <p class="desi-hero__sub">
                {{ $partidos->total() }} partido{{ $partidos->total() !== 1 ? 's' : '' }}
                @if(request()->hasAny(['torneo','estado','fecha','division']))
                · <span style="color:#4f8ef7">filtrado{{ $partidos->total() !== 1 ? 's' : '' }}</span>
                @endif
            </p>
        </div>

        @can('crear-designaciones')
        <a href="{{ route('designaciones.create') }}" class="btn btn-primary desi-btn-nuevo">
            <i class="fa-solid fa-plus"></i>
            Nuevo partido
        </a>
        @endcan
    </div>

    {{-- ═══ FILTROS ═══ --}}
    <form method="GET" action="{{ route('designaciones.index') }}" class="desi-filter-bar">
        <div class="desi-filter-item">
            <label class="desi-filter-label">Torneo</label>
            <select name="torneo" class="filter-select" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach($torneos as $t)
                    <option value="{{ $t->idTorneo }}" {{ request('torneo') == $t->idTorneo ? 'selected' : '' }}>
                        {{ $t->nombreTorneo }} · {{ $t->temporada }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Estado</label>
            <select name="estado" class="filter-select" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach(['borrador'=>'Borrador','programado'=>'Programado','confirmado'=>'Confirmado','critico'=>'Crítico','aplazado'=>'Aplazado','en_curso'=>'En curso','finalizado'=>'Finalizado','cancelado'=>'Cancelado'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('estado') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Fecha</label>
            <input type="text" name="fecha" class="form-input" data-nova-date
                   value="{{ request('fecha') }}" placeholder="dd/mm/aaaa" style="max-width:160px">
        </div>

        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            @if(request()->hasAny(['torneo','estado','fecha','division']))
            <a href="{{ route('designaciones.index') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Limpiar
            </a>
            @endif
        </div>
    </form>

    {{-- ═══ LISTA DE PARTIDOS ═══ --}}
    <div class="desi-list">
        @forelse($partidos as $partido)
        @php
            $estado     = $partido->estadoPartido;
            $esCritico  = $estado === 'critico';
            $esEnCurso  = $estado === 'en_curso';
            $esTerminal = in_array($estado, ['finalizado','cancelado']);
            $fechaHuman = $partido->fechaPartido?->locale('es')->isoFormat('ddd D [de] MMMM');
            $esHoy      = $partido->fechaPartido?->isToday();
            $esMañana   = $partido->fechaPartido?->isTomorrow();

            $estadoLabel = ['borrador'=>'Borrador','programado'=>'Programado','confirmado'=>'Confirmado',
                            'critico'=>'Crítico','aplazado'=>'Aplazado','en_curso'=>'En curso',
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
                            @elseif($esEnCurso)
                                <span class="desi-live-dot"></span>
                            @endif
                            {{ $estadoLabel }}
                        </span>

                        @if($esHoy)
                        <span class="desi-hoy-badge"><i class="fa-solid fa-circle" style="font-size:.45rem"></i> HOY</span>
                        @elseif($esMañana)
                        <span class="desi-manana-badge"><i class="fa-solid fa-sun" style="font-size:.7rem"></i> Mañana</span>
                        @endif

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
                    @if($partido->torneo)
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-trophy"></i>
                        {{ $partido->torneo->nombreTorneo }}
                    </span>
                    @endif
                    @if($partido->division)
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-layer-group"></i>
                        {{ $partido->division->nombreDivision }}
                    </span>
                    @endif
                    <span class="desi-meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $partido->sede?->nombreSede ?? 'Sin sede' }}
                        @if($partido->sede?->municipio) · {{ $partido->sede->municipio }}@endif
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
                </a>
            </div>

        </div>
        @empty

        <div class="empty-state">
            <i class="fa-solid fa-clipboard-list" style="font-size:3rem;color:var(--text-muted);margin-bottom:1.25rem"></i>
            <p class="empty-state__title">No hay partidos registrados</p>
            <p class="empty-state__sub">
                @can('crear-designaciones')
                <a href="{{ route('designaciones.create') }}" class="btn btn-primary" style="margin-top:.75rem">
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
<script>window.colegioId = {{ Auth::user()->idColegio }};</script>
@vite(['resources/js/designaciones/designaciones.js'])
@endpush
