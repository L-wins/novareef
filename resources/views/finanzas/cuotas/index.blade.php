@extends('layouts.app')

@section('titulo', 'Cuotas del mes')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\MovimientoFinanciero;
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Cuotas del mes</h1>
            <p class="page-subheading">Quién pagó la mensualidad este mes y quién sigue pendiente — o sin generar.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    <form method="GET" class="cm-toolbar" style="margin-bottom:1rem;">
        <input type="month" name="mes" value="{{ $mes }}" class="form-input" style="max-width:180px" onchange="this.form.submit()">
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th class="text-right">Monto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>
                            <a href="{{ route('finanzas.arbitro.show', $fila['arbitro']->idArbitro) }}" class="td-primary">
                                {{ $fila['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $fila['arbitro']->idArbitro }}
                            </a>
                        </td>
                        <td class="text-right">
                            {{ $fila['monto'] !== null ? '$' . number_format((float) $fila['monto'], 0, ',', '.') : '—' }}
                        </td>
                        <td>
                            @if ($fila['estado'] === null)
                                <span class="badge badge-gray">No generada</span>
                            @else
                                @php [$etiqueta, $color] = MovimientoFinanciero::ETIQUETAS_ESTADO[$fila['estado']]; @endphp
                                <span class="badge badge-{{ $color }}">{{ $etiqueta }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
