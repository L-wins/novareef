@extends('layouts.app')

@section('titulo', 'Árbitros')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Árbitros</h1>
            <p class="page-subheading" data-auto-filter-region="contador">
                @include('arbitros.partials.contador')
            </p>
        </div>
        @can('crear-arbitros')
        <a href="{{ route('arbitros.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nuevo árbitro
        </a>
        @endcan
    </div>

    @include('partials.limite-plan-banner', [
        'recurso'    => 'árbitros',
        'usados'     => $limiteUsados,
        'limite'     => $limite,
        'porcentaje' => $limitePorcentaje,
    ])

    {{-- Barra de búsqueda + filtros --}}
    <form method="GET" action="{{ route('arbitros.index') }}" class="filter-bar filter-bar-grid" data-auto-filter data-auto-filter-ajax>
        <div class="filter-group filter-grow">
            <label class="filter-label">Buscar</label>
            <input type="text" name="buscar"
                   value="{{ request('buscar') }}"
                   placeholder="Buscar por nombre, documento o carnet..."
                   class="filter-input">
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
            @if (request('buscar') || request('estado') || request('categoria') || request('orden'))
                <a href="{{ route('arbitros.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    <div data-auto-filter-region="resultados">
        @include('arbitros.partials.resultados')
    </div>

    @can('editar-arbitros')
        <div class="archived-link-wrap">
            <a href="{{ route('arbitros.archivados') }}" class="archived-link">
                <i class="fa-solid fa-box-archive"></i>
                Ver árbitros archivados
            </a>
        </div>
    @endcan

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
