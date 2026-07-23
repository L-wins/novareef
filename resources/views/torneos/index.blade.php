@extends('layouts.app')

@section('titulo', 'Torneos')
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
@php
    $tieneFiltros = request()->hasAny(['estado', 'tipo', 'temporada']);
@endphp

<div class="container torneos-page">

    <div class="page-header page-header--panel">
        <div class="page-header-left">
            <span class="page-kicker">Gestión competitiva</span>
            <h1 class="page-heading">Torneos</h1>
            <p class="page-subheading">
                {{ $torneos->total() }} torneo{{ $torneos->total() === 1 ? '' : 's' }}
                {{ $torneos->total() === 1 ? 'encontrado' : 'encontrados' }}
            </p>
        </div>

        @can('crear-torneos')
            <a href="{{ route('torneos.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nuevo torneo
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('torneos.index') }}" class="filter-bar-grid filter-panel" data-auto-filter>
        <div class="filter-panel__head">
            <span><i class="fa-solid fa-filter"></i> Filtros</span>
            @if ($tieneFiltros)
                <a href="{{ route('torneos.index') }}" class="filter-clear">
                    <i class="fa-solid fa-xmark"></i>
                    Limpiar
                </a>
            @endif
        </div>

        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" class="filter-select"
                    data-nova-select data-placeholder="Estado">
                <option value="">Todos</option>
                @foreach (['proximo' => 'Próximo', 'activo' => 'Activo', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipo" class="filter-select"
                    data-nova-select data-placeholder="Tipo">
                <option value="">Todos</option>
                @foreach (['local' => 'Local', 'zonal' => 'Zonal', 'oficial' => 'Oficial'] as $val => $label)
                    <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Temporada</label>
            <select name="temporada" class="filter-select"
                    data-nova-select data-searchable="true" data-placeholder="Temporada">
                <option value="">Todas</option>
                @foreach ($temporadasDisponibles as $t)
                    <option value="{{ $t }}" {{ (string) request('temporada') === (string) $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>
        </div>
    </form>

    @if ($torneos->isEmpty())
        <div class="empty-state empty-state--torneos">
            <span class="empty-state-icon"><i class="fa-solid fa-trophy"></i></span>
            @if ($tieneFiltros)
                <h2>Sin resultados</h2>
                <p>No hay torneos que coincidan con los filtros seleccionados.</p>
                <a href="{{ route('torneos.index') }}" class="btn btn-secondary">Ver todos</a>
            @else
                <h2>No hay torneos registrados</h2>
                <p>Crea el primer torneo para configurar divisiones, sedes, tarifas y partidos.</p>
                @can('crear-torneos')
                    <a href="{{ route('torneos.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Crear primer torneo
                    </a>
                @endcan
            @endif
        </div>
    @else
        <div class="torneos-grid">
            @foreach ($torneos as $torneo)
                <div class="torneo-card">
                    <div class="torneo-card__head">
                        <div class="torneo-card__identity">
                            <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="torneo-card__title">
                                {{ $torneo->nombreTorneo }}
                            </a>
                            <span class="torneo-card__season">Temporada {{ $torneo->temporada }}</span>
                        </div>
                    </div>

                    <div class="torneo-card__badges">
                        <span class="t-badge" data-tipo="{{ $torneo->tipoTorneo }}">{{ ucfirst($torneo->tipoTorneo) }}</span>
                        <span class="t-badge" data-estado="{{ $torneo->estadoTorneo }}">{{ ucfirst($torneo->estadoTorneo) }}</span>
                    </div>

                    <div class="torneo-card__meta">
                        <div>
                            <span>Inicio</span>
                            <strong><i class="fa-solid fa-calendar-day"></i>{{ $torneo->fechaInicio->format('d/m/Y') }}</strong>
                        </div>
                        <div>
                            <span>Fin</span>
                            <strong><i class="fa-solid fa-flag-checkered"></i>{{ $torneo->fechaFin->format('d/m/Y') }}</strong>
                        </div>
                        <div class="span-2">
                            <span>Organizador</span>
                            <strong><i class="fa-solid fa-user-tie"></i>{{ $torneo->organizadorNombre }}</strong>
                        </div>
                    </div>

                    <div class="torneo-card__counts">
                        <span><strong>{{ $torneo->divisiones_count }}</strong> división{{ $torneo->divisiones_count === 1 ? '' : 'es' }}</span>
                        <span><strong>{{ $torneo->partidos_count }}</strong> partido{{ $torneo->partidos_count === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="torneo-card__actions">
                        <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-eye"></i>
                            Ver
                        </a>
                        @can('editar-torneos')
                            <a href="{{ route('torneos.edit', $torneo->idTorneo) }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Editar
                            </a>
                            <a href="{{ route('torneos.perfil', $torneo->idTorneo) }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-sliders"></i>
                                Perfil
                            </a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        @if ($torneos->hasPages())
            <div class="pagination-wrapper">{{ $torneos->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
@endpush
