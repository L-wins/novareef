@extends('layouts.app')

@section('titulo', 'Sanciones')
@section('seccion', 'Sanciones')

@push('styles')
    @vite(['resources/css/sanciones/sanciones.css'])
@endpush

@php
    $etiquetasEstado = [
        'activa'   => ['Activa', 'amber'],
        'cumplida' => ['Cumplida', 'green'],
        'anulada'  => ['Anulada', 'red'],
        'apelada'  => ['Apelada', 'blue'],
    ];
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $esArbitro ? 'Mis sanciones' : 'Sanciones' }}</h1>
            <p class="page-subheading">
                {{ $esArbitro ? 'Historial de sanciones disciplinarias registradas a tu nombre.' : 'Registro disciplinario de los árbitros del colegio.' }}
            </p>
        </div>
        @can('crear-sanciones')
            @if (!$esArbitro)
            <a href="{{ route('sanciones.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nueva sanción
            </a>
            @endif
        @endcan
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    @if (!$esArbitro)
    <form method="GET" action="{{ route('sanciones.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Árbitro</label>
            <select name="idArbitro" class="filter-select" data-nova-select data-searchable="true" data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach ($arbitros as $arbitro)
                    <option value="{{ $arbitro->idArbitro }}" {{ (string) request('idArbitro') === (string) $arbitro->idArbitro ? 'selected' : '' }}>
                        {{ $arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $arbitro->idArbitro }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" class="filter-select">
                <option value="">Todos</option>
                @foreach ($etiquetasEstado as $val => [$label, $color])
                    <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>Filtrar</button>
            @if (request()->anyFilled(['idArbitro', 'estado']))
                <a href="{{ route('sanciones.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>
    @endif

    @if ($sanciones->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-shield-halved" style="font-size:48px;"></i>
            <p>{{ $esArbitro ? 'No tienes sanciones registradas.' : 'No hay sanciones que coincidan con los filtros.' }}</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        @if (!$esArbitro)<th>Árbitro</th>@endif
                        <th>Tipo</th>
                        <th>Fecha del hecho</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sanciones as $sancion)
                        @php [$estadoLabel, $estadoColor] = $etiquetasEstado[$sancion->estadoSancion] ?? ['—', 'gray']; @endphp
                        <tr>
                            @if (!$esArbitro)
                            <td>{{ $sancion->arbitro->usuario->nombreUsuario ?? '—' }}</td>
                            @endif
                            <td>
                                <span class="td-primary">{{ $sancion->tipo->etiqueta ?? '—' }}</span>
                                @if ($sancion->tieneMultaEconomica)
                                    <span class="td-secondary">Con multa económica</span>
                                @endif
                            </td>
                            <td>{{ $sancion->fechaHecho->format('d/m/Y') }}</td>
                            <td>
                                {{ $sancion->fechaInicioSancion->format('d/m/Y') }}
                                @if ($sancion->fechaFinSancion)
                                    — {{ $sancion->fechaFinSancion->format('d/m/Y') }}
                                @else
                                    — indefinida
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('sanciones.show', $sancion->idSancion) }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i>
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($sanciones->hasPages())
            <div class="pagination-wrapper">{{ $sanciones->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
