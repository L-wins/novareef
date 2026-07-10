@extends('layouts.app')

@section('titulo', 'Reportes financieros')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\MovimientoFinanciero;
    $etiquetasCategoria = MovimientoFinanciero::ETIQUETAS_CATEGORIA;
    $comparativa = $reporte['comparativa'];

    $deltaClase = fn (?float $v, bool $subirEsBueno = true): string => $v === null
        ? 'fin-stat__delta--neutral'
        : ($v >= 0 === $subirEsBueno ? 'fin-stat__delta--up' : 'fin-stat__delta--down');

    $deltaTexto = fn (?float $v): string => $v === null
        ? 'sin base de comparación'
        : ($v >= 0 ? '↑ +' : '↓ ') . number_format($v, 1, ',', '.') . '% vs período anterior';
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Reportes financieros</h1>
            <p class="page-subheading">Ingresos, egresos y neto para cualquier rango de fechas.</p>
        </div>
        <a href="{{ route('finanzas.reportes.pdf', request()->only(['desde', 'hasta'])) }}" class="btn btn-secondary">
            <i class="fa-solid fa-file-pdf"></i>
            Exportar PDF
        </a>
    </div>

    @include('finanzas.partials.subnav')

    <form method="GET" action="{{ route('finanzas.reportes.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Desde</label>
            <input type="text" name="desde" id="reporte-desde" value="{{ $desde }}" data-nova-date placeholder="dd/mm/aaaa" class="filter-input">
        </div>
        <div class="filter-group">
            <label class="filter-label">Hasta</label>
            <input type="text" name="hasta" id="reporte-hasta" value="{{ $hasta }}" data-nova-date placeholder="dd/mm/aaaa" class="filter-input">
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>Filtrar</button>
        </div>
        <div class="filter-group filter-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-atajo-reporte="mes">Este mes</button>
            <button type="button" class="btn btn-secondary btn-sm" data-atajo-reporte="trimestre">Trimestre</button>
            <button type="button" class="btn btn-secondary btn-sm" data-atajo-reporte="anio">Este año</button>
        </div>
    </form>

    <div class="fin-stats">
        <div class="fin-stat">
            <p class="fin-stat__label">Total ingresos</p>
            <p class="fin-stat__value monto-ingreso">${{ number_format($reporte['totalIngresos'], 0, ',', '.') }}</p>
            <p class="fin-stat__delta {{ $deltaClase($comparativa['variacionIngresos']) }}">{{ $deltaTexto($comparativa['variacionIngresos']) }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Total egresos</p>
            <p class="fin-stat__value monto-egreso">${{ number_format($reporte['totalEgresos'], 0, ',', '.') }}</p>
            <p class="fin-stat__delta {{ $deltaClase($comparativa['variacionEgresos'], subirEsBueno: false) }}">{{ $deltaTexto($comparativa['variacionEgresos']) }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Neto</p>
            <p class="fin-stat__value">${{ number_format($reporte['neto'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">
                Período anterior ({{ \Illuminate\Support\Carbon::parse($comparativa['desde'])->format('d/m/Y') }}
                – {{ \Illuminate\Support\Carbon::parse($comparativa['hasta'])->format('d/m/Y') }}):
                ${{ number_format($comparativa['neto'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    @include('finanzas.partials.chart-mensual', ['serie' => $serie])

    @if ($reporte['porCategoria']->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-chart-column"></i>
            <p>No hay movimientos registrados en este rango de fechas.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reporte['porCategoria'] as $fila)
                        <tr>
                            <td class="td-primary">{{ $etiquetasCategoria[$fila['categoria']] ?? $fila['categoria'] }}</td>
                            <td>
                                <span class="badge {{ $fila['tipoMovimiento'] === 'ingreso' ? 'badge-green' : 'badge-red' }}">
                                    {{ $fila['tipoMovimiento'] === 'ingreso' ? 'Ingreso' : 'Egreso' }}
                                </span>
                            </td>
                            <td class="text-right">{{ $fila['cantidad'] }}</td>
                            <td class="text-right">
                                <span class="{{ $fila['tipoMovimiento'] === 'ingreso' ? 'monto-ingreso' : 'monto-egreso' }}">
                                    ${{ number_format($fila['total'], 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
