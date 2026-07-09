@extends('layouts.app')

@section('titulo', 'Pago acumulado a árbitro')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Pago acumulado a árbitro</h1>
            <p class="page-subheading">Salda varios pagos de nómina de una vez, compensando deudas pendientes.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('finanzas.pagos-arbitro.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group" style="min-width:260px;">
            <label class="filter-label">Árbitro</label>
            <select name="idArbitro" class="filter-select" data-nova-select data-searchable="true" data-placeholder="Selecciona un árbitro">
                <option value="">— Selecciona —</option>
                @foreach ($arbitros as $arbitro)
                    <option value="{{ $arbitro->idArbitro }}" {{ (string) request('idArbitro') === (string) $arbitro->idArbitro ? 'selected' : '' }}>
                        {{ $arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $arbitro->idArbitro }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>Ver pendientes</button>
        </div>
    </form>

    @if ($arbitroSeleccionado)
        @if ($movimientosNomina->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-circle-check" style="font-size:48px;"></i>
                <p>{{ $arbitroSeleccionado->usuario->nombreUsuario ?? 'Este árbitro' }} no tiene pagos de nómina pendientes.</p>
            </div>
        @else
            <form method="POST" action="{{ route('finanzas.pagos-arbitro.store') }}" class="form-card" id="form-pago-acumulado">
                @csrf
                <input type="hidden" name="idArbitro" value="{{ $arbitroSeleccionado->idArbitro }}">

                <div class="form-section">
                    <p class="form-section-title">Pagos de nómina pendientes</p>
                    <div class="table-card" style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Torneo</th>
                                    <th class="text-right">Saldo pendiente</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movimientosNomina as $mov)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="idsMovimientosNomina[]" value="{{ $mov->idMovimiento }}"
                                                   class="check-nomina" data-saldo="{{ $mov->saldoPendiente() }}" checked>
                                        </td>
                                        <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                                        <td>{{ $mov->concepto }}</td>
                                        <td>{{ $mov->partido->torneo->nombreTorneo ?? '—' }}</td>
                                        <td class="text-right">${{ number_format($mov->saldoPendiente(), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($deudas->isNotEmpty())
                <div class="form-section">
                    <p class="form-section-title">Deudas pendientes a compensar (opcional)</p>
                    <div class="table-card" style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th class="text-right">Saldo pendiente</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deudas as $deuda)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="idsDeudasNetear[]" value="{{ $deuda->idMovimiento }}"
                                                   class="check-deuda" data-saldo="{{ $deuda->saldoPendiente() }}">
                                        </td>
                                        <td>{{ $deuda->fechaMovimiento->format('d/m/Y') }}</td>
                                        <td>{{ $deuda->concepto }}</td>
                                        <td class="text-right">${{ number_format($deuda->saldoPendiente(), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <div class="form-section">
                    <p class="form-section-title">Datos del pago</p>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Fecha <span class="req">*</span></label>
                            <input type="text" name="fecha" data-nova-date placeholder="dd/mm/aaaa" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Método de pago <span class="req">*</span></label>
                            <select name="metodoPago" data-nova-select class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="consignacion">Consignación</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label">Referencia</label>
                            <input type="text" name="referencia" maxlength="100" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="border-bottom:none;">
                    <div class="cuenta-resumen-pago">
                        <div>
                            <p class="form-label">Total nómina seleccionada</p>
                            <p id="total-nomina" class="monto-ingreso" style="font-size:1.1rem;">$0.00</p>
                        </div>
                        <div>
                            <p class="form-label">Total deudas a compensar</p>
                            <p id="total-deudas" class="monto-egreso" style="font-size:1.1rem;">$0.00</p>
                        </div>
                        <div>
                            <p class="form-label">Neto a desembolsar</p>
                            <p id="total-neto" style="font-size:1.2rem;font-weight:700;">$0.00</p>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i>
                        Registrar pago
                    </button>
                </div>
            </form>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
