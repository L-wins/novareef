@extends('layouts.app')

@section('titulo', 'Reportes financieros')
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

    <div class="cuenta-resumen-pago" style="margin-bottom:1.5rem;">
        <div>
            <p class="form-label">Total ingresos</p>
            <p class="monto-ingreso" style="font-size:1.3rem;">${{ number_format($reporte['totalIngresos'], 2) }}</p>
        </div>
        <div>
            <p class="form-label">Total egresos</p>
            <p class="monto-egreso" style="font-size:1.3rem;">${{ number_format($reporte['totalEgresos'], 2) }}</p>
        </div>
        <div>
            <p class="form-label">Neto</p>
            <p style="font-size:1.4rem;font-weight:700;">${{ number_format($reporte['neto'], 2) }}</p>
        </div>
    </div>

    @if ($reporte['porCategoria']->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-chart-column" style="font-size:48px;"></i>
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
                                    ${{ number_format($fila['total'], 2) }}
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
