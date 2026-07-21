@extends('layouts.app')

@section('titulo', 'Árbitros en mora')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Árbitros en mora</h1>
            <p class="page-subheading">Árbitros que le deben al colegio (mensualidad/multa pendiente), ordenados por antigüedad de la deuda.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    @if ($mora->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <p>Ningún árbitro está en mora ahora mismo.</p>
        </div>
    @else
        <div class="cm-toolbar">
            <input type="search" data-balance-filtro placeholder="Buscar árbitro…" class="form-input cm-toolbar__buscar" autocomplete="off">
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Árbitro</th>
                        <th class="text-right">Nos debe</th>
                        <th class="text-right">Días en mora</th>
                        <th>Antigüedad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mora as $fila)
                        <tr data-balance-fila data-nombre="{{ mb_strtolower($fila['arbitro']->usuario->nombreUsuario ?? '') }}">
                            <td>
                                <a href="{{ route('finanzas.arbitro.show', $fila['arbitro']->idArbitro) }}" class="td-primary">
                                    {{ $fila['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $fila['arbitro']->idArbitro }}
                                </a>
                            </td>
                            <td class="text-right">
                                <span class="monto-ingreso">${{ number_format($fila['nosDebe'], 0, ',', '.') }}</span>
                            </td>
                            <td class="text-right">{{ $fila['diasMora'] }}</td>
                            <td>
                                @php
                                    $colorBucket = match ($fila['bucket']) {
                                        '0-30'  => 'badge-green',
                                        '31-60' => 'badge-amber',
                                        default => 'badge-red',
                                    };
                                @endphp
                                <span class="badge {{ $colorBucket }}">{{ $fila['bucket'] }} días</span>
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
