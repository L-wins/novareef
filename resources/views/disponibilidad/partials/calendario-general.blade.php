{{-- Región reemplazable vía AJAX (auto-filter.js) al navegar entre semanas.
     Recibe: $arbitros, $semana, $franjas — ver DisponibilidadController::general(). --}}
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
           class="disp-week-nav__btn" title="Semana anterior" aria-label="Semana anterior">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <span class="disp-week-label">
            {{ $semana->lunes->translatedFormat('d/m') }} — {{ $semana->domingo->translatedFormat('d/m/Y') }}
        </span>
        <a href="{{ route('disponibilidad.general', ['semana' => $semana->semanaNext]) }}"
           class="disp-week-nav__btn" title="Semana siguiente" aria-label="Semana siguiente">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        @if (! $semana->esActual())
            <a href="{{ route('disponibilidad.general') }}" class="disp-week-nav__today">
                <i class="fa-solid fa-calendar-day"></i> Hoy
            </a>
        @endif
        <a href="{{ route('designaciones.estadisticas') }}" class="btn btn-secondary disp-stats-btn">
            <i class="fa-solid fa-chart-column"></i> Estadísticas
        </a>
    </div>
</div>

@if ($arbitros->isEmpty())
    <div class="detail-empty">No hay árbitros activos registrados en este colegio.</div>
@else
    <div class="disp-table-wrap">
        <table class="disp-table">
            <thead>
                <tr>
                    <th class="disp-th-arbitro">Árbitro</th>
                    @foreach ($semana->dias as $dia)
                        @php $esHoyGen = $dia->isToday(); @endphp
                        <th class="{{ $esHoyGen ? 'col-hoy' : '' }}">
                            {{ ucfirst($dia->locale('es')->isoFormat('ddd')) }}&nbsp;{{ $dia->format('d/m') }}
                            @if ($esHoyGen)
                                <span class="disp-day-hoy-badge">Hoy</span>
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
                            <span class="disp-arbitro-nombre">
                                {{ $arb->usuario?->nombreUsuario ?? '—' }}
                            </span>
                            @if ($arb->categoria)
                                <span class="disp-arbitro-categoria">
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
                                @if ($disp && $disp->esDisponible())
                                    <span class="disp-cell-disponible">
                                        <i class="fa-solid fa-circle"></i>
                                        {{ $franjas[$disp->franjaHoraria] ?? $disp->franjaHoraria }}
                                    </span>
                                @elseif ($disp)
                                    <span class="disp-cell-nodisp-explicito">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        No disponible
                                    </span>
                                @else
                                    <span class="disp-cell-nodisp" title="Aún no reportó su disponibilidad">
                                        <i class="fa-solid fa-clock"></i>
                                        Sin reporte
                                    </span>
                                @endif
                                @foreach ($extras as $ext)
                                    <span class="disp-cell-extraordinaria" title="{{ $ext->motivo }}">
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

    <p class="disp-info-note">
        <i class="fa-solid fa-circle-info"></i>
        Verde = disponible. Rojo = marcó "No disponible". "—" = aún no reportó esta semana. "Extraord." = indisponibilidad extraordinaria registrada.
    </p>
@endif
