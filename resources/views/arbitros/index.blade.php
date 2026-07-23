@extends('layouts.app')

@section('titulo', 'Árbitros')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">
    @php
        $tieneFiltros = request()->filled('buscar') || request()->filled('estado') || request()->filled('categoria') || request()->filled('orden');
        $estadoActual = request('estado');
    @endphp

    {{-- Cabecera --}}
    <div class="page-header page-header--panel">
        <div class="page-header-left">
            <span class="page-kicker">Registro operativo</span>
            <h1 class="page-heading">Árbitros</h1>
            <p class="page-subheading" data-auto-filter-region="contador">
                @include('arbitros.partials.contador')
            </p>
        </div>
        <div class="page-header-actions">
            @can('editar-arbitros')
                <a href="{{ route('categorias.arbitro.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-tags"></i>
                    Categorías
                </a>
                <a href="{{ route('arbitros.archivados') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-box-archive"></i>
                    Archivados
                </a>
            @endcan
            @can('crear-arbitros')
            <a href="{{ route('arbitros.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nuevo árbitro
            </a>
            @endcan
        </div>
    </div>

    @include('partials.limite-plan-banner', [
        'recurso'    => 'árbitros',
        'usados'     => $limiteUsados,
        'limite'     => $limite,
        'porcentaje' => $limitePorcentaje,
    ])

    <div class="status-filter-strip" aria-label="Filtros rápidos por estado">
        <a href="{{ route('arbitros.index', request()->except('estado', 'page')) }}"
           class="status-filter-chip {{ $estadoActual ? '' : 'is-active' }}">
            <span>Todos</span>
            <strong>{{ $totalActivos }}</strong>
        </a>
        @foreach ($estados as $est)
            @php $estadoParams = array_merge(request()->except('page'), ['estado' => $est->nombre]); @endphp
            <a href="{{ route('arbitros.index', $estadoParams) }}"
               class="status-filter-chip {{ $estadoActual === $est->nombre ? 'is-active' : '' }}"
               data-color="{{ $est->color ?? 'gray' }}">
                <span>{{ $est->etiqueta }}</span>
                <strong>{{ (int) $resumenEstados->get($est->nombre, 0) }}</strong>
            </a>
        @endforeach
    </div>

    {{-- Barra de búsqueda + filtros --}}
    <form method="GET" action="{{ route('arbitros.index') }}" class="filter-bar filter-bar-grid filter-panel" data-auto-filter data-auto-filter-ajax>
        <div class="filter-group filter-grow filter-search">
            <label class="filter-label">Buscar</label>
            <i class="fa-solid fa-magnifying-glass filter-search-icon"></i>
            <input type="text" name="buscar"
                   value="{{ request('buscar') }}"
                   placeholder="Buscar por nombre, documento o carnet..."
                   class="filter-input">
            @if (request('buscar'))
                <a href="{{ route('arbitros.index', request()->except('buscar', 'page')) }}"
                   class="filter-search-clear"
                   aria-label="Limpiar búsqueda">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>

        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" class="filter-select">
                <option value="">Todos</option>
                @foreach ($estados as $est)
                    <option value="{{ $est->nombre }}" {{ request('estado') === $est->nombre ? 'selected' : '' }}>
                        {{ $est->etiqueta }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($categorias->isNotEmpty())
        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select name="categoria" class="filter-select">
                <option value="">Todas</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->idCategoria }}"
                        {{ (string) request('categoria') === (string) $cat->idCategoria ? 'selected' : '' }}>
                        {{ $cat->nombreCategoria }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="filter-group">
            <label class="filter-label">Orden</label>
            <select name="orden" class="filter-select">
                @foreach ([
                    'nombre_asc'  => 'Nombre A→Z',
                    'nombre_desc' => 'Nombre Z→A',
                    'fecha_desc'  => 'Ingreso reciente',
                    'fecha_asc'   => 'Ingreso antiguo',
                    'carnet_asc'  => 'Carné A→Z',
                ] as $val => $label)
                    <option value="{{ $val }}" {{ (request('orden', 'nombre_asc')) === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i>
                Buscar
            </button>
            @if ($tieneFiltros)
                <a href="{{ route('arbitros.index') }}" class="filter-clear">
                    <i class="fa-solid fa-rotate-left"></i>
                    Limpiar
                </a>
            @endif
        </div>
    </form>

    <div data-auto-filter-region="resultados">
        @include('arbitros.partials.resultados')
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
