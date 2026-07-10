@extends('layouts.app')

@section('titulo', 'Finanzas')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\MovimientoFinanciero;
    $etiquetasCategoria = MovimientoFinanciero::ETIQUETAS_CATEGORIA;
    $etiquetasEstado    = MovimientoFinanciero::ETIQUETAS_ESTADO;
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
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    {{-- Resumen del período filtrado (los anulados nunca suman) --}}
    <div class="fin-stats">
        <div class="fin-stat">
            <p class="fin-stat__label">Ingresos</p>
            <p class="fin-stat__value monto-ingreso">${{ number_format($resumen['totalIngresos'], 0, ',', '.') }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Egresos</p>
            <p class="fin-stat__value monto-egreso">${{ number_format($resumen['totalEgresos'], 0, ',', '.') }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Neto</p>
            <p class="fin-stat__value">${{ number_format($resumen['neto'], 0, ',', '.') }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Por cobrar</p>
            <p class="fin-stat__value">${{ number_format($resumen['pendientePorCobrar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Ingresos sin abonar por completo</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Por pagar</p>
            <p class="fin-stat__value">${{ number_format($resumen['pendientePorPagar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Egresos sin pagar por completo</p>
        </div>
    </div>

    <form method="GET" action="{{ route('finanzas.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Concepto…"
                   class="filter-input" autocomplete="off">
        </div>

        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipoMovimiento" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                <option value="ingreso" {{ request('tipoMovimiento') === 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                <option value="egreso"  {{ request('tipoMovimiento') === 'egreso'  ? 'selected' : '' }}>Egreso</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select name="categoria" data-nova-select data-placeholder="Todas">
                <option value="">Todas</option>
                @foreach ($etiquetasCategoria as $val => $label)
                    <option value="{{ $val }}" {{ request('categoria') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" data-nova-select data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach ($etiquetasEstado as $val => [$label, $color])
                    <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Árbitro</label>
            <select name="idArbitro" data-nova-select data-searchable="true" data-placeholder="Todos">
                <option value="">Todos</option>
                @foreach ($arbitrosFiltro as $arb)
                    <option value="{{ $arb->idArbitro }}" {{ (string) request('idArbitro') === (string) $arb->idArbitro ? 'selected' : '' }}>
                        {{ $arb->usuario->nombreUsuario ?? 'Árbitro #' . $arb->idArbitro }}
                    </option>
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
            @if (request()->anyFilled(['q', 'tipoMovimiento', 'categoria', 'estado', 'idArbitro', 'desde', 'hasta']))
                <a href="{{ route('finanzas.index') }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="fin-toolbar">
        <span class="fin-toolbar__info">
            {{ $resumen['cantidad'] }} {{ $resumen['cantidad'] === 1 ? 'movimiento' : 'movimientos' }} en el período
        </span>
        <a href="{{ route('finanzas.exportar', request()->query()) }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-file-csv"></i>
            Exportar CSV
        </a>
    </div>

    @if ($movimientos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-sack-dollar"></i>
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
                        @php
                            [$estadoLabel, $estadoColor] = $etiquetasEstado[$mov->estadoMovimiento] ?? ['—', 'gray'];
                            $abonado = (float) $mov->montoTotal - $mov->saldoPendiente();
                        @endphp
                        <tr>
                            <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $mov->esIngreso() ? 'badge-green' : 'badge-red' }}">
                                    {{ $mov->esIngreso() ? 'Ingreso' : 'Egreso' }}
                                </span>
                            </td>
                            <td>{{ $mov->etiquetaCategoria() }}</td>
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
                                    ${{ number_format((float) $mov->montoTotal, 0, ',', '.') }}
                                </span>
                                @if ($mov->estadoMovimiento === MovimientoFinanciero::ESTADO_PARCIAL)
                                    <span class="monto-abonado">abonado ${{ number_format($abonado, 0, ',', '.') }}</span>
                                @endif
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
