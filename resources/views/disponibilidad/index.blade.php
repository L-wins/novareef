@extends('layouts.app')

@section('titulo', 'Mi Disponibilidad')
@section('seccion', 'Disponibilidad')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- ═══ HERO ═══ --}}
    <div class="disp-hero">
        <div class="disp-hero__left">
            <span class="disp-hero__label">Módulo de disponibilidad</span>
            <h1 class="disp-hero__title">Mi Disponibilidad</h1>
            <p class="disp-hero__subtitle">
                Semana del
                <strong>{{ ucfirst($semana->lunes->locale('es')->isoFormat('dddd D [de] MMMM')) }}</strong>
                al
                <strong>{{ ucfirst($semana->domingo->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</strong>
            </p>
        </div>

        <div class="disp-hero__right">
            @if ($yaGuardo)
                <div class="disp-status-badge guardado">
                    <i class="fa-solid fa-circle-check"></i>
                    Disponibilidad guardada para esta semana
                </div>
            @else
                <div class="disp-status-badge pendiente">
                    <i class="fa-solid fa-clock"></i>
                    Pendiente — Reporta antes del próximo {{ $nombreDia }}
                </div>
                <button type="submit"
                        form="form-disponibilidad"
                        class="disp-save-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar disponibilidad
                </button>
            @endif
        </div>
    </div>

    {{-- ═══ BANNER DE GUARDADO ═══ --}}
    @if ($yaGuardo)
        <div class="disp-saved-banner">
            <i class="fa-solid fa-circle-check"></i>
            <span>
                Tu disponibilidad fue registrada correctamente para esta semana.
                Podrás actualizarla el próximo <strong>{{ $nombreDia }}</strong>.
            </span>
        </div>
    @endif

    {{-- ═══ LEYENDA DE FRANJAS ═══ --}}
    @php
        $franjaColores = [
            'am'         => ['color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)', 'hora' => '6:00–12:00'],
            'pm'         => ['color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'hora' => '12:00–18:00'],
            'noche'      => ['color' => '#60a5fa', 'bg' => 'rgba(96,165,250,0.12)', 'hora' => '18:00–22:00'],
            'am_pm'      => ['color' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.12)',  'hora' => '6:00–18:00'],
            'am_noche'   => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)', 'hora' => '6:00–22:00'],
            'pm_noche'   => ['color' => '#a78bfa', 'bg' => 'rgba(167,139,250,0.12)','hora' => '12:00–22:00'],
            'todo_el_dia'=> ['color' => '#4f8ef7', 'bg' => 'rgba(79,142,247,0.12)', 'hora' => '6:00–22:00'],
        ];
    @endphp
    <div class="disp-legend-strip">
        <span class="disp-legend-label">
            <i class="fa-solid fa-clock" style="font-size:0.7rem;"></i>
            Franjas
        </span>
        @foreach ($franjas as $clave => $etiqueta)
            @php $color = $franjaColores[$clave] ?? ['color'=>'#8892a4','bg'=>'rgba(74,85,104,0.12)','hora'=>'']; @endphp
            <span class="disp-legend-chip"
                  style="color:{{ $color['color'] }};background:{{ $color['bg'] }};border-color:{{ $color['color'] }}40;">
                {{ $etiqueta }}
                <span class="disp-legend-chip__time">{{ $color['hora'] }}</span>
            </span>
        @endforeach
    </div>

    {{-- ═══ GRID DE DÍAS ═══ --}}
    <form id="form-disponibilidad"
          method="POST"
          action="{{ route('disponibilidad.store') }}">
        @csrf

        <div class="disp-grid">
            @foreach ($semana->dias as $dia)
                @php
                    $key          = $dia->format('Y-m-d');
                    $esPasado     = $dia->isPast() && !$dia->isToday();
                    $esHoy        = $dia->isToday();
                    $dispDia      = $disponibilidades[$key] ?? null;
                    $extrasDia    = $indisponibilidades[$key] ?? collect();
                    $franjaActual = $dispDia?->franjaHoraria;
                    $bloqueado    = $yaGuardo || $esPasado;

                    $clases = ['disp-day-card'];
                    if ($esHoy)                          $clases[] = 'is-today';
                    if ($esPasado)                       $clases[] = 'is-past';
                    if ($franjaActual && !$esPasado)     $clases[] = 'is-available';
                    if ($extrasDia->isNotEmpty())        $clases[] = 'is-extraordinary';
                @endphp

                <div class="{{ implode(' ', $clases) }}"
                     data-day-card="{{ $key }}"
                     data-franja="{{ $franjaActual ?? '' }}">

                    <input type="hidden"
                           name="disponibilidades[{{ $loop->index }}][fecha]"
                           value="{{ $key }}">

                    {{-- Header del card --}}
                    <div class="disp-day-card__header">
                        <div>
                            <div class="disp-day-name">
                                {{ ucfirst($dia->locale('es')->isoFormat('dddd')) }}
                            </div>
                            <div class="disp-day-date">
                                {{ $dia->locale('es')->isoFormat('D [de] MMMM') }}
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                            @if ($esHoy)
                                <span class="disp-badge today">
                                    <i class="fa-solid fa-circle" style="font-size:0.35rem;"></i>
                                    Hoy
                                </span>
                            @endif
                            @if ($extrasDia->isNotEmpty())
                                <span class="disp-badge extraordinary"
                                      title="{{ $extrasDia->first()->motivo }}">
                                    <i class="fa-solid fa-triangle-exclamation" style="font-size:0.6rem;"></i>
                                    Extraordinaria
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="disp-day-divider"></div>

                    {{-- Select de franja --}}
                    <div class="disp-day-select-wrap">
                        @if ($esPasado)
                            <input type="hidden"
                                   name="disponibilidades[{{ $loop->index }}][franja]"
                                   value="">
                        @else
                            <select name="disponibilidades[{{ $loop->index }}][franja]"
                                    class="form-select"
                                    data-nova-select
                                    data-placeholder="No disponible"
                                    {{ $bloqueado ? 'disabled' : '' }}>
                                <option value="">No disponible</option>
                                @foreach ($franjas as $clave => $etiqueta)
                                    <option value="{{ $clave }}"
                                        {{ $franjaActual === $clave ? 'selected' : '' }}>
                                        {{ $etiqueta }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($yaGuardo && !$esPasado)
                                {{-- Campo oculto para mantener el valor en el DOM al estar bloqueado --}}
                                <input type="hidden"
                                       name="disponibilidades[{{ $loop->index }}][franja]"
                                       value="{{ $franjaActual ?? '' }}">
                            @endif
                        @endif
                    </div>

                    {{-- Estado actual --}}
                    @if (!$esPasado)
                        <div class="disp-day-status" data-state-badge>
                            @if ($franjaActual)
                                <i class="fa-solid fa-circle-check" style="color:#22c55e;font-size:0.8rem;"></i>
                                <span style="color:#22c55e;">{{ $franjas[$franjaActual] }}</span>
                            @else
                                <i class="fa-solid fa-circle-xmark" style="color:#4a5568;font-size:0.8rem;"></i>
                                <span style="color:#8892a4;">No disponible</span>
                            @endif
                        </div>
                    @endif

                </div>
            @endforeach
        </div>

    </form>

    {{-- ═══ INDISPONIBILIDAD EXTRAORDINARIA ═══ --}}
    <div class="disp-extraordinary-section">

        <div class="disp-extraordinary-divider">
            <span class="disp-extraordinary-title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Indisponibilidad extraordinaria
            </span>
        </div>

        <p class="disp-extraordinary-subtitle">
            Usa esto solo si algo urgente cambia tu disponibilidad ya reportada.
            El designador será notificado automáticamente si tienes partidos confirmados en ese horario.
        </p>

        <form method="POST"
              action="{{ route('disponibilidad.extraordinaria') }}">
            @csrf

            <div class="disp-form-grid">

                <div class="form-group">
                    <label class="form-label">¿Qué día no puedes? <span class="req">*</span></label>
                    <input type="text"
                           name="fechaAfectada"
                           class="form-input"
                           data-nova-date
                           data-min-date="today"
                           placeholder="Selecciona una fecha"
                           value="{{ old('fechaAfectada') }}">
                    @error('fechaAfectada')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">¿Qué horario? <span class="req">*</span></label>
                    <select name="franjaAfectada"
                            class="form-select"
                            data-nova-select
                            data-placeholder="Selecciona una franja">
                        <option value="">Selecciona una franja</option>
                        @foreach ($franjas as $clave => $etiqueta)
                            <option value="{{ $clave }}"
                                {{ old('franjaAfectada') === $clave ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    @error('franjaAfectada')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Motivo <span class="req">*</span>
                        <span id="contador-extraordinaria"
                              style="font-size:0.75rem;color:#8892a4;margin-left:0.4rem;font-weight:400;">
                            0/300
                        </span>
                    </label>
                    <textarea id="motivo-extraordinaria"
                              name="motivo"
                              maxlength="300"
                              class="form-textarea"
                              style="resize:none;height:80px;"
                              placeholder="Ej: Cita médica urgente, accidente, compromiso familiar...">{{ old('motivo') }}</textarea>
                    @error('motivo')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>

            <button type="submit"
                    id="btn-extraordinaria"
                    class="disp-report-btn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Reportar indisponibilidad
            </button>

            <p class="disp-extraordinary-note">
                <i class="fa-solid fa-circle-info" style="font-size:0.85rem;"></i>
                Esta acción no reemplaza tu disponibilidad semanal.
                Solo marca este horario puntual como no disponible para el designador.
            </p>

        </form>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/disponibilidad/disponibilidad.js'])
@endpush
