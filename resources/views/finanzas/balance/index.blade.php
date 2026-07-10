@extends('layouts.app')

@section('titulo', 'Balance financiero')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Balance financiero</h1>
            <p class="page-subheading">Saldo en caja y cuánto se debe con cada árbitro, hoy.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    <div class="cuenta-resumen-pago mb-fin">
        <div>
            <p class="form-label">Saldo en caja</p>
            <p class="monto-hero {{ $balance['saldoEnCaja'] < 0 ? 'monto-egreso' : 'monto-ingreso' }}">
                ${{ number_format($balance['saldoEnCaja'], 0, ',', '.') }}
            </p>
            <p class="field-hint">Dinero realmente cobrado menos realmente pagado (no incluye pendientes).</p>
        </div>
        <div>
            <p class="form-label">Total que le debemos a árbitros</p>
            <p class="monto-egreso monto-lg">${{ number_format($balance['totalLeDebemos'], 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="form-label">Total que nos deben los árbitros</p>
            <p class="monto-ingreso monto-lg">${{ number_format($balance['totalNosDeben'], 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($balance['porArbitro']->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <p>No hay saldos pendientes con ningún árbitro en este momento.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Árbitro</th>
                        <th class="text-right">Le debemos</th>
                        <th class="text-right">Nos debe</th>
                        <th class="text-right">Neto</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balance['porArbitro'] as $fila)
                        @php $neto = $fila['leDebemos'] - $fila['nosDebe']; @endphp
                        <tr>
                            <td class="td-primary">{{ $fila['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $fila['arbitro']->idArbitro }}</td>
                            <td class="text-right">
                                @if ($fila['leDebemos'] > 0)
                                    <span class="monto-egreso">${{ number_format($fila['leDebemos'], 0, ',', '.') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($fila['nosDebe'] > 0)
                                    <span class="monto-ingreso">${{ number_format($fila['nosDebe'], 0, ',', '.') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="{{ $neto >= 0 ? 'monto-egreso' : 'monto-ingreso' }}">${{ number_format(abs($neto), 0, ',', '.') }}</span>
                                <span class="td-secondary">{{ $neto >= 0 ? 'a favor del árbitro' : 'a favor del colegio' }}</span>
                            </td>
                            <td class="text-right">
                                @can('crear-finanzas')
                                    @if ($fila['leDebemos'] > 0)
                                        <a href="{{ route('finanzas.pagos-arbitro.index', ['idArbitro' => $fila['arbitro']->idArbitro]) }}" class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-hand-holding-dollar"></i> Pagar
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
