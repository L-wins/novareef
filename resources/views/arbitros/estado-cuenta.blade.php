@extends('layouts.app')

@section('titulo', 'Mi estado de cuenta')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@php
    $etiquetasEstado = [
        'pendiente' => ['Pendiente', 'gray'],
        'parcial'   => ['Parcial', 'blue'],
        'pagado'    => ['Pagada', 'green'],
        'anulado'   => ['Anulada', 'red'],
    ];
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.mi-perfil') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a mi perfil
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Mi estado de cuenta</h1>
            <p class="page-subheading">Lo que el colegio te debe por partidos en nómina y tus multas pendientes.</p>
        </div>
    </div>

    <div class="detail-card cuenta-resumen cuenta-mb">
        <div>
            <p class="cuenta-saldo-label">Saldo pendiente por cobrar</p>
            <p class="cuenta-saldo-valor {{ $estadoCuenta['saldoPendienteCobrar'] > 0 ? 'tiene-saldo' : '' }}">
                ${{ number_format($estadoCuenta['saldoPendienteCobrar'], 0, ',', '.') }}
            </p>
        </div>
        <i class="fa-solid fa-sack-dollar cuenta-icono"></i>
    </div>

    <div class="detail-card cuenta-mb">
        <p class="detail-section-title">Partidos pendientes de pago</p>
        @if ($estadoCuenta['pendientesPorPartido']->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-circle-check"></i>
                <p>No tienes partidos pendientes de pago.</p>
            </div>
        @else
            <div class="table-card cuenta-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Torneo</th>
                            <th>Partido</th>
                            <th class="text-right">Monto</th>
                            <th class="text-right">Saldo pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['pendientesPorPartido'] as $mov)
                        <tr>
                            <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                            <td>{{ $mov->torneo->nombreTorneo ?? '—' }}</td>
                            <td>
                                @if ($mov->partido)
                                    {{ $mov->partido->equipoLocal }} vs {{ $mov->partido->equipoVisitante }}
                                @else
                                    {{ $mov->concepto }}
                                @endif
                            </td>
                            <td class="text-right">${{ number_format((float) $mov->montoTotal, 0, ',', '.') }}</td>
                            <td class="text-right"><span class="monto-negativo">${{ number_format($mov->saldoPendiente(), 0, ',', '.') }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="detail-card cuenta-mb">
        <p class="detail-section-title">Historial de pagos recibidos</p>
        @if ($estadoCuenta['historialPagos']->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>Aún no has recibido pagos registrados.</p>
            </div>
        @else
            <div class="table-card cuenta-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Torneo</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th class="text-right">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['historialPagos'] as $abono)
                        <tr>
                            <td>{{ $abono->fechaAbono->format('d/m/Y') }}</td>
                            <td>{{ $abono->movimiento->torneo->nombreTorneo ?? '—' }}</td>
                            <td><span class="monto-positivo">${{ number_format((float) $abono->monto, 0, ',', '.') }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $abono->metodoPago)) }}</td>
                            <td class="text-right">
                                @if ($abono->idLotePago)
                                    <a href="{{ route('arbitros.estado-cuenta.comprobante', $abono->idLotePago) }}" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                @else
                                    <span class="td-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="detail-card cuenta-mb">
        <p class="detail-section-title">Historial de multas</p>
        @if ($estadoCuenta['historialMultas']->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-circle-check"></i>
                <p>No tienes multas registradas.</p>
            </div>
        @else
            <div class="table-card cuenta-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Valor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['historialMultas'] as $multa)
                            @php [$label, $color] = $etiquetasEstado[$multa->estadoMovimiento] ?? ['—', 'gray']; @endphp
                            <tr>
                                <td>{{ $multa->fechaMovimiento->format('d/m/Y') }}</td>
                                <td>{{ $multa->concepto }}</td>
                                <td><span class="monto-negativo">${{ number_format((float) $multa->montoTotal, 0, ',', '.') }}</span></td>
                                <td><span class="badge badge-{{ $color }}">{{ $label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="detail-card">
        <p class="detail-section-title">Descuentos aplicados en nómina</p>
        @if ($estadoCuenta['descuentosNomina']->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-scale-balanced"></i>
                <p>No se te han aplicado descuentos en nómina.</p>
            </div>
        @else
            <div class="table-card cuenta-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto compensado</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['descuentosNomina'] as $descuento)
                        <tr>
                            <td>{{ $descuento->fechaAbono->format('d/m/Y') }}</td>
                            <td>{{ $descuento->movimiento->concepto ?? '—' }}</td>
                            <td><span class="monto-negativo">${{ number_format((float) $descuento->monto, 0, ',', '.') }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
