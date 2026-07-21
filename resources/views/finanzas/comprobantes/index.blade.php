@extends('layouts.app')

@section('titulo', 'Comprobantes del mes')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Comprobantes del mes</h1>
            <p class="page-subheading">Pagos de nómina y cobros de mensualidad, archivados por mes calendario.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    <form method="GET" class="cm-toolbar" style="margin-bottom:1rem;">
        <input type="month" name="mes" value="{{ $mes }}" class="form-input" style="max-width:180px" onchange="this.form.submit()">
    </form>

    @if ($comprobantes->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-file-invoice"></i>
            <p>No hay comprobantes registrados en este mes.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Árbitro</th>
                        <th>Tipo</th>
                        <th class="text-right">Monto</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comprobantes as $fila)
                        <tr>
                            <td class="td-primary">
                                {{ $fila['arbitro']?->usuario->nombreUsuario ?? '—' }}
                            </td>
                            <td>
                                @if ($fila['tipo'] === 'nomina')
                                    <span class="badge badge-blue">Pago de nómina</span>
                                @else
                                    <span class="badge badge-green">Cobro de mensualidad</span>
                                @endif
                            </td>
                            <td class="text-right">${{ number_format($fila['monto'], 0, ',', '.') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                            <td>
                                @if ($fila['arbitro'])
                                    @if ($fila['tipo'] === 'nomina')
                                        <a href="{{ route('finanzas.arbitro.comprobante', [$fila['arbitro']->idArbitro, $fila['idLotePago']]) }}" class="btn btn-ghost btn-sm">
                                            <i class="fa-solid fa-download"></i> Descargar
                                        </a>
                                    @else
                                        <a href="{{ route('finanzas.arbitro.comprobante-cobro', [$fila['arbitro']->idArbitro, $fila['idLotePago']]) }}" class="btn btn-ghost btn-sm">
                                            <i class="fa-solid fa-download"></i> Descargar
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
