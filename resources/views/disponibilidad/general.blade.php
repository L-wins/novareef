@extends('layouts.app')

@section('titulo', 'Disponibilidad general')
@section('seccion', 'Disponibilidad')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Header con navegación de semana --}}
    <div class="disp-general-header">
        <div class="page-header-left">
            <h1 class="page-heading">Disponibilidad semanal</h1>
            <p class="page-subheading">
                Árbitros activos del colegio ·
                semana del {{ $semana->lunes->translatedFormat('d/m') }} al {{ $semana->domingo->translatedFormat('d/m/Y') }}
            </p>
        </div>
        <div class="disp-week-nav">
            <a href="{{ route('disponibilidad.general', ['semana' => $semana->semanaPrev]) }}"
               class="btn btn-secondary btn-sm" title="Semana anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <span class="disp-week-label">
                {{ $semana->lunes->translatedFormat('d/m') }} — {{ $semana->domingo->translatedFormat('d/m/Y') }}
            </span>
            <a href="{{ route('disponibilidad.general', ['semana' => $semana->semanaNext]) }}"
               class="btn btn-secondary btn-sm" title="Semana siguiente">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
            @if (! $semana->esActual())
                <a href="{{ route('disponibilidad.general') }}"
                   class="btn btn-secondary btn-sm" style="margin-left:0.25rem;">
                    Hoy
                </a>
            @endif
        </div>
    </div>

    @if ($arbitros->isEmpty())
        <div class="detail-empty">No hay árbitros activos registrados en este colegio.</div>
    @else
        <div class="disp-table-wrap">
            <table class="disp-table">
                <thead>
                    <tr>
                        <th style="text-align:left;min-width:160px;">Árbitro</th>
                        @foreach ($semana->dias as $dia)
                            @php $esHoyGen = $dia->isToday(); @endphp
                            <th class="{{ $esHoyGen ? 'col-hoy' : '' }}">
                                {{ ucfirst($dia->locale('es')->isoFormat('ddd')) }}&nbsp;{{ $dia->format('d/m') }}<br>
                                @if ($esHoyGen)
                                    <span style="display:block;font-size:0.65rem;color:#4f8ef7;font-weight:700;text-transform:uppercase;margin-top:2px;">Hoy</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($arbitros as $arb)
                        @php
                            $dispArbitro   = $arb->disponibilidades->keyBy(fn ($d) => $d->fechaDisponibilidad->format('Y-m-d'));
                            $extrasArbitro = $arb->indisponibilidadesExtraordinarias->groupBy(fn ($i) => $i->fechaAfectada->format('Y-m-d'));
                        @endphp
                        <tr>
                            <td>
                                <span style="font-weight:600;color:var(--disp-text);">
                                    {{ $arb->usuario?->nombreUsuario ?? '—' }}
                                </span>
                                @if ($arb->categoria)
                                    <span style="display:block;font-size:0.72rem;color:var(--disp-text-2);">
                                        {{ $arb->categoria?->nombreCategoria ?? '' }}
                                    </span>
                                @endif
                            </td>
                            @foreach ($semana->dias as $dia)
                                @php
                                    $key    = $dia->format('Y-m-d');
                                    $disp   = $dispArbitro[$key] ?? null;
                                    $extras = $extrasArbitro[$key] ?? collect();
                                    $esHoyC = $dia->isToday();
                                @endphp
                                <td class="{{ $esHoyC ? 'col-hoy' : '' }}">
                                    @if ($disp)
                                        <span class="disp-cell-disponible">
                                            <i class="fa-solid fa-circle" style="font-size:0.4rem;"></i>
                                            {{ $franjas[$disp->franjaHoraria] ?? $disp->franjaHoraria }}
                                        </span>
                                    @else
                                        <span class="disp-cell-nodisp">—</span>
                                    @endif
                                    @foreach ($extras as $ext)
                                        <span class="disp-cell-extraordinaria"
                                              title="{{ $ext->motivo }}">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Extraord.
                                        </span>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p style="font-size:0.78rem;color:var(--text-muted);margin-top:0.85rem;">
            <i class="fa-solid fa-circle-info" style="color:var(--accent);margin-right:0.3rem;"></i>
            Verde = disponible. "—" = sin reporte (no disponible). "Extraord." = indisponibilidad extraordinaria registrada.
        </p>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
