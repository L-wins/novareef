@extends('layouts.app')

@section('titulo', 'Torneos')
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Torneos</h1>
            <p class="page-subheading">
                {{ $torneos->total() }} torneo{{ $torneos->total() === 1 ? '' : 's' }}
                {{ $torneos->total() === 1 ? 'registrado' : 'registrados' }}
            </p>
        </div>
        @can('crear-torneos')
        <a href="{{ route('torneos.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nuevo torneo
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('torneos.index') }}" class="filter-bar-grid">
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
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>
            @if (request()->hasAny(['estado', 'tipo', 'temporada']))
                <a href="{{ route('torneos.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    @if ($torneos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-trophy" style="font-size:48px;margin-bottom:1rem;opacity:.45;"></i>
            @if (request()->hasAny(['estado', 'tipo', 'temporada']))
                <p>No hay torneos que coincidan con los filtros.</p>
                <a href="{{ route('torneos.index') }}" class="btn btn-secondary" style="margin-top:1rem;">Ver todos</a>
            @else
                <p>No hay torneos registrados todavía.</p>
                @can('crear-torneos')
                <a href="{{ route('torneos.create') }}" class="btn btn-primary" style="margin-top:1rem;">
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
                        <div>
                            <h3 class="torneo-card__title">{{ $torneo->nombreTorneo }}</h3>
                            <span class="torneo-card__season">Temporada {{ $torneo->temporada }}</span>
                        </div>
                    </div>

                    <div class="torneo-card__badges">
                        <span class="t-badge" data-tipo="{{ $torneo->tipoTorneo }}">{{ ucfirst($torneo->tipoTorneo) }}</span>
                        <span class="t-badge" data-estado="{{ $torneo->estadoTorneo }}">{{ ucfirst($torneo->estadoTorneo) }}</span>
                    </div>

                    <div class="torneo-card__meta">
                        <div><i class="fa-solid fa-calendar-day"></i>{{ $torneo->fechaInicio->format('d/m/Y') }}</div>
                        <div><i class="fa-solid fa-flag-checkered"></i>{{ $torneo->fechaFin->format('d/m/Y') }}</div>
                        <div class="span-2"><i class="fa-solid fa-user-tie"></i>{{ $torneo->organizadorNombre }}</div>
                    </div>

                    <div class="torneo-card__counts">
                        <span><strong>{{ $torneo->divisiones_count }}</strong> división{{ $torneo->divisiones_count === 1 ? '' : 'es' }}</span>
                        <span>·</span>
                        <span><strong>{{ $torneo->partidos_count }}</strong> partido{{ $torneo->partidos_count === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="torneo-card__actions">
                        <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-secondary btn-sm">
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
