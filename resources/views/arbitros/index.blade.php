@extends('layouts.app')

@section('titulo', 'Árbitros')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    @if (session('success'))
        <div id="flash-msg" class="flash-success">{{ session('success') }}</div>
    @elseif (session('error'))
        <div id="flash-msg" class="flash-error">{{ session('error') }}</div>
    @endif

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Árbitros</h1>
            <p class="page-subheading">
                {{ $arbitros->total() }} árbitro{{ $arbitros->total() === 1 ? '' : 's' }}
                {{ $arbitros->total() === 1 ? 'encontrado' : 'encontrados' }}
            </p>
        </div>
        @can('crear-arbitros')
        <a href="{{ route('arbitros.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nuevo árbitro
        </a>
        @endcan
    </div>

    {{-- Barra de búsqueda + filtros --}}
    <form method="GET" action="{{ route('arbitros.index') }}" class="filter-bar filter-bar-grid">
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
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i>
                Buscar
            </button>
            @if (request('buscar') || request('estado') || request('categoria') || request('orden'))
                <a href="{{ route('arbitros.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    @if ($arbitros->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-user-slash" style="font-size:48px;margin-bottom:1rem;opacity:.5;"></i>
            @if (request('buscar') || request('estado') || request('categoria'))
                <p>No hay árbitros que coincidan con los filtros aplicados.</p>
                <a href="{{ route('arbitros.index') }}" class="btn btn-secondary" style="margin-top:1rem;">
                    Ver todos
                </a>
            @else
                <p>No hay árbitros registrados todavía.</p>
                @can('crear-arbitros')
                <a href="{{ route('arbitros.create') }}" class="btn btn-primary" style="margin-top:1rem;">
                    Registrar primer árbitro
                </a>
                @endcan
            @endif
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Carné</th>
                        <th>Árbitro</th>
                        <th>Documento</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($arbitros as $arbitro)
                    <tr>
                        <td class="td-code">{{ $arbitro->codigoCarnet }}</td>
                        <td>
                            <div class="cell-with-avatar">
                                @if ($arbitro->fotoPerfil)
                                    <img src="{{ asset('storage/' . $arbitro->fotoPerfil) }}"
                                         alt="{{ $arbitro->usuario->nombreUsuario }}"
                                         class="avatar avatar-sm">
                                @else
                                    <span class="avatar avatar-sm avatar-initials">
                                        {{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 1)) }}
                                    </span>
                                @endif
                                <div>
                                    <span class="td-primary">{{ $arbitro->usuario->nombreUsuario }}</span>
                                    <span class="td-secondary">{{ $arbitro->usuario->emailUsuario }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="td-primary">{{ $arbitro->numeroDocumento }}</span>
                            <span class="td-secondary">{{ ucfirst($arbitro->tipoDocumento) }}</span>
                        </td>
                        <td>
                            <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                        </td>
                        <td>
                            @php $est = $arbitro->estado; @endphp
                            <span class="estado-pill" data-color="{{ $est->color ?? 'gray' }}">
                                {{ $est->etiqueta ?? ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}"
                                   class="btn-icon btn-icon-view" title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @can('editar-arbitros')
                                <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}"
                                   class="btn-icon btn-icon-edit" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($arbitros->hasPages())
            <div class="pagination-wrapper">{{ $arbitros->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
