@extends('layouts.app')

@section('titulo', 'Historial de partidos')
@section('seccion', 'Mis Partidos')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('mis-partidos.index') }}">Mis Partidos</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Historial</span>
    </div>

    {{-- ═══ HERO + ESTADÍSTICAS ═══ --}}
    <div class="mis-hero">
        <div class="mis-hero__left">
            <div class="mis-hero__label">Panel del árbitro</div>
            <h1 class="mis-hero__saludo">Historial de partidos</h1>
            <p class="mis-hero__sub">Tu trayectoria como árbitro: partidos dirigidos y estadísticas.</p>
        </div>
        <div class="mis-hero__stats">
            <div class="mis-stat mis-stat--dirigidos">
                <span class="mis-stat__num">{{ $stats['totalDirigidos'] }}</span>
                <span class="mis-stat__label">Dirigidos</span>
            </div>
            <div class="mis-stat">
                <span class="mis-stat__num">{{ $stats['torneos'] }}</span>
                <span class="mis-stat__label">Torneos</span>
            </div>
            <div class="mis-stat {{ $stats['rechazadas'] > 0 ? 'mis-stat--rechazadas' : '' }}">
                <span class="mis-stat__num">{{ $stats['rechazadas'] }}</span>
                <span class="mis-stat__label">Rechazadas</span>
            </div>
        </div>
    </div>

    {{-- ═══ DESGLOSE POR ROL ═══ --}}
    @if($stats['porRol']->isNotEmpty())
    <div class="mis-roles-strip">
        <span class="mis-roles-strip__label"><i class="fa-solid fa-user-tie"></i> Por rol:</span>
        @foreach($stats['porRol'] as $rol => $total)
        <span class="mis-rol-stat">
            <strong>{{ $total }}</strong> {{ $rol }}
        </span>
        @endforeach
    </div>
    @endif

    {{-- ═══ FILTROS + EXPORTAR ═══ --}}
    <form method="GET" action="{{ route('mis-partidos.historial') }}" class="desi-filter-bar" data-auto-filter>
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
            <label class="desi-filter-label">Rol</label>
            <select name="rol" class="filter-select" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach($roles as $r)
                    <option value="{{ $r->idRol }}" {{ request('rol') == $r->idRol ? 'selected' : '' }}>
                        {{ $r->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Mi estado</label>
            <select name="estado" class="filter-select" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach(['confirmada'=>'Confirmada','rechazada'=>'Rechazada','pendiente'=>'Pendiente'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('estado') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Desde</label>
            <input type="text" name="desde" class="form-input" data-nova-date
                   value="{{ request('desde') }}" placeholder="dd/mm/aaaa" style="max-width:150px">
        </div>

        <div class="desi-filter-item">
            <label class="desi-filter-label">Hasta</label>
            <input type="text" name="hasta" class="form-input" data-nova-date
                   value="{{ request('hasta') }}" placeholder="dd/mm/aaaa" style="max-width:150px">
        </div>

        <div class="desi-filter-actions">
            <button type="submit" class="btn btn-secondary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            @if(request()->hasAny(['torneo','rol','estado','desde','hasta']))
            <a href="{{ route('mis-partidos.historial') }}" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Limpiar
            </a>
            @endif
            <a href="{{ route('mis-partidos.historial.pdf', request()->query()) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-file-pdf"></i> Exportar PDF
            </a>
        </div>
    </form>

    {{-- ═══ TABLA ═══ --}}
    @if($historial->isNotEmpty())
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Partido</th>
                    <th>Fecha</th>
                    <th>Torneo</th>
                    <th>Rol</th>
                    <th>Pago</th>
                    <th>Mi estado</th>
                    <th>Estado partido</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($historial as $desig)
                @php $partido = $desig->partido; @endphp
                <tr>
                    <td>{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</td>
                    <td>{{ $partido->fechaPartido?->locale('es')->isoFormat('D/M/YYYY') }}</td>
                    <td>{{ $partido->torneo?->nombreTorneo ?? '—' }}</td>
                    <td>{{ $desig->rol?->nombre ?? '—' }}</td>
                    <td>@include('mis-partidos.partials.pago-chip', ['pago' => $desig->pago])</td>
                    <td>
                        <span class="partido-estado-badge estado-{{ $desig->estadoDesignacion }}" style="font-size:.72rem">
                            {{ ucfirst($desig->estadoDesignacion) }}
                        </span>
                    </td>
                    <td>
                        <span class="partido-estado-badge estado-{{ $partido->estadoPartido }}" style="font-size:.72rem">
                            {{ ucfirst(str_replace('_', ' ', $partido->estadoPartido)) }}
                        </span>
                    </td>
                    <td>
                        @if($desig->estaConfirmada())
                        <a href="{{ route('mis-partidos.detalle', $partido->idPartido) }}"
                           class="desi-gestionar-btn" title="Ver detalle" style="width:32px;height:32px">
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $historial->links() }}
    </div>
    @else
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left" style="font-size:2.5rem;color:var(--text-muted);margin-bottom:1rem"></i>
        <p class="empty-state__title">Sin resultados</p>
        <p class="empty-state__sub">
            @if(request()->hasAny(['torneo','rol','estado','desde','hasta']))
                Ningún partido coincide con los filtros seleccionados.
            @else
                Tus partidos pasados aparecerán aquí.
            @endif
        </p>
    </div>
    @endif

</div>
@endsection
