@extends('layouts.app')

@section('titulo', 'Finanzas')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    $etiquetasCategoria = [
        'ingreso_torneo'      => 'Ingreso por torneo',
        'mensualidad'         => 'Mensualidad',
        'multa'               => 'Multa',
        'otro_ingreso'        => 'Otro ingreso',
        'nomina_arbitro'      => 'Nómina de árbitros',
        'arbitro_externo'     => 'Árbitro externo',
        'gasto_fijo'          => 'Gasto fijo',
        'gasto_institucional' => 'Gasto institucional',
        'gasto_vario'         => 'Gasto vario',
    ];
    $etiquetasEstado = [
        'pendiente' => ['Pendiente', 'gray'],
        'parcial'   => ['Parcial', 'amber'],
        'pagado'    => ['Pagado', 'green'],
        'anulado'   => ['Anulado', 'red'],
    ];
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Finanzas</h1>
            <p class="page-subheading">Ingresos, egresos y movimientos financieros del colegio.</p>
        </div>
        @can('crear-finanzas')
        <a href="{{ route('finanzas.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nuevo movimiento
        </a>
        @endcan
    </div>

    @include('finanzas.partials.subnav')

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('finanzas.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipoMovimiento" class="filter-select">
                <option value="">Todos</option>
                <option value="ingreso" {{ request('tipoMovimiento') === 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                <option value="egreso"  {{ request('tipoMovimiento') === 'egreso'  ? 'selected' : '' }}>Egreso</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select name="categoria" class="filter-select">
                <option value="">Todas</option>
                @foreach ($etiquetasCategoria as $val => $label)
                    <option value="{{ $val }}" {{ request('categoria') === $val ? 'selected' : '' }}>{{ $label }}</option>
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

        <div class="filter-group">
            <label class="filter-label">Desde</label>
            <input type="text" name="desde" value="{{ request('desde') }}" data-nova-date placeholder="dd/mm/aaaa" class="filter-input">
        </div>

        <div class="filter-group">
            <label class="filter-label">Hasta</label>
            <input type="text" name="hasta" value="{{ request('hasta') }}" data-nova-date placeholder="dd/mm/aaaa" class="filter-input">
        </div>

        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>
            @if (request()->anyFilled(['tipoMovimiento', 'categoria', 'estado', 'desde', 'hasta']))
                <a href="{{ route('finanzas.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    @if ($movimientos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-sack-dollar" style="font-size:48px;"></i>
            <p>No hay movimientos financieros que coincidan con los filtros.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Concepto</th>
                        <th>Árbitro / Torneo</th>
                        <th class="text-right">Monto</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimientos as $mov)
                        @php [$estadoLabel, $estadoColor] = $etiquetasEstado[$mov->estadoMovimiento] ?? ['—', 'gray']; @endphp
                        <tr>
                            <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $mov->esIngreso() ? 'badge-green' : 'badge-red' }}">
                                    {{ $mov->esIngreso() ? 'Ingreso' : 'Egreso' }}
                                </span>
                            </td>
                            <td>{{ $etiquetasCategoria[$mov->categoria] ?? $mov->categoria }}</td>
                            <td>
                                <span class="td-primary">{{ $mov->concepto }}</span>
                            </td>
                            <td>
                                @if ($mov->arbitro)
                                    <span class="td-primary">{{ $mov->arbitro->usuario->nombreUsuario ?? '—' }}</span>
                                @elseif ($mov->nombreArbitroExterno)
                                    <span class="td-primary">{{ $mov->nombreArbitroExterno }}</span>
                                    <span class="td-secondary">Externo</span>
                                @elseif ($mov->torneo)
                                    <span class="td-primary">{{ $mov->torneo->nombreTorneo }}</span>
                                @else
                                    <span class="td-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="{{ $mov->esIngreso() ? 'monto-ingreso' : 'monto-egreso' }}">
                                    ${{ number_format((float) $mov->montoTotal, 2) }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('finanzas.show', $mov->idMovimiento) }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i>
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($movimientos->hasPages())
            <div class="pagination-wrapper">{{ $movimientos->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
