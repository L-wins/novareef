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
            <p class="page-subheading">{{ $arbitros->total() }} árbitro{{ $arbitros->total() === 1 ? '' : 's' }} encontrado{{ $arbitros->total() === 1 ? '' : 's' }}</p>
        </div>
        @can('crear-arbitros')
        <a href="{{ route('arbitros.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
            Nuevo árbitro
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('arbitros.index') }}" class="filter-bar">
        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" class="filter-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach([
                    'proceso_ingreso' => 'Proceso de ingreso',
                    'activo'          => 'Activo',
                    'inactivo'        => 'Inactivo',
                    'suspendido'      => 'Suspendido',
                    'retirado'        => 'Retirado',
                ] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($categorias->isNotEmpty())
        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select name="categoria" class="filter-select" onchange="this.form.submit()">
                <option value="">Todas</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->idCategoria }}" {{ (string) request('categoria') === (string) $cat->idCategoria ? 'selected' : '' }}>
                        {{ $cat->nombreCategoria }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        @if (request('estado') || request('categoria'))
        <a href="{{ route('arbitros.index') }}" class="filter-clear">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
            </svg>
            Limpiar filtros
        </a>
        @endif
    </form>

    @if ($arbitros->isEmpty())
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
            </svg>
            @if (request('estado') || request('categoria'))
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
                            <span class="td-primary">{{ $arbitro->usuario->nombreUsuario }}</span>
                            <span class="td-secondary">{{ $arbitro->usuario->emailUsuario }}</span>
                        </td>
                        <td>
                            <span class="td-primary">{{ $arbitro->numeroDocumento }}</span>
                            <span class="td-secondary">{{ ucfirst($arbitro->tipoDocumento) }}</span>
                        </td>
                        <td>
                            <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $arbitro->estadoArbitro }}">
                                {{ ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}"
                                   class="btn-icon btn-icon-view" title="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                @can('editar-arbitros')
                                <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}"
                                   class="btn-icon btn-icon-edit" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                                    </svg>
                                </a>
                                <form method="POST"
                                      action="{{ route('arbitros.toggleEstado', $arbitro->idArbitro) }}"
                                      style="display:contents">
                                    @csrf
                                    @method('PUT')
                                    <button type="button"
                                            class="btn-icon btn-icon-estado"
                                            data-confirm="¿Avanzar el estado de {{ $arbitro->usuario->nombreUsuario }}?"
                                            title="Cambiar estado">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </form>
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
