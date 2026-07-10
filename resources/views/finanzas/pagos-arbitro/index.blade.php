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
        <div class="flash-success">
            {{ session('success') }}
            @if (session('lotePago'))
                <a href="{{ route('finanzas.pagos-arbitro.comprobante', session('lotePago')) }}">
                    <i class="fa-solid fa-file-pdf"></i> Descargar comprobante
                </a>
            @endif
        </div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('finanzas.pagos-arbitro.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Árbitro</label>
            <select name="idArbitro" data-nova-select data-searchable="true" data-placeholder="Selecciona un árbitro">
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
                <i class="fa-solid fa-circle-check"></i>
                <p>{{ $arbitroSeleccionado->usuario->nombreUsuario ?? 'Este árbitro' }} no tiene pagos de nómina pendientes.</p>
            </div>
        @else
            <form method="POST" action="{{ route('finanzas.pagos-arbitro.store') }}" class="form-card" id="form-pago-acumulado">
                @csrf
                <input type="hidden" name="idArbitro" value="{{ $arbitroSeleccionado->idArbitro }}">

                <div class="form-section">
                    <p class="form-section-title">Pagos de nómina pendientes</p>
                    <div class="table-card table-scroll">
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
                                        <td class="text-right">${{ number_format($mov->saldoPendiente(), 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($deudas->isNotEmpty())
                <div class="form-section">
                    <p class="form-section-title">Deudas pendientes a compensar (opcional)</p>
                    <div class="table-card table-scroll">
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
                                        <td class="text-right">${{ number_format($deuda->saldoPendiente(), 0, ',', '.') }}</td>
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

                <div class="form-section form-section--sin-borde">
                    <div class="cuenta-resumen-pago">
                        <div>
                            <p class="form-label">Total nómina seleccionada</p>
                            <p id="total-nomina" class="monto-ingreso monto-md">$0</p>
                        </div>
                        <div>
                            <p class="form-label">Total deudas a compensar</p>
                            <p id="total-deudas" class="monto-egreso monto-md">$0</p>
                        </div>
                        <div>
                            <p class="form-label">Neto a desembolsar</p>
                            <p id="total-neto" class="monto-md-bold">$0</p>
                        </div>
                    </div>
                </div>

                <div class="form-actions-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check"></i>
                        Registrar pago
                    </button>
                </div>
            </form>
        @endif

        {{-- Comprobantes de pagos anteriores --}}
        @if ($lotesRecientes->isNotEmpty())
        <div class="form-card">
            <div class="form-section form-section--sin-borde">
                <p class="form-section-title">Últimos pagos realizados</p>
                <div class="fin-lotes">
                    @foreach ($lotesRecientes as $lote)
                        <div class="fin-lote">
                            <span class="fin-lote__info">
                                <i class="fa-solid fa-receipt"></i>
                                {{ \Illuminate\Support\Carbon::parse($lote->fecha)->format('d/m/Y') }}
                                <span class="fin-lote__monto">${{ number_format($lote->neto, 0, ',', '.') }}</span>
                            </span>
                            <a href="{{ route('finanzas.pagos-arbitro.comprobante', $lote->idLotePago) }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file-pdf"></i> Comprobante
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
