@extends('layouts.app')

@section('titulo', 'Configuración — Colegio')
@section('seccion', 'Configuración')

@push('styles')
    @vite(['resources/css/configuracion/configuracion.css'])
@endpush

@push('scripts')
    @vite(['resources/js/configuracion/configuracion.js'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Configuración</h1>
            <p class="page-subheading">Reglas propias de este colegio — cada colegio define las suyas.</p>
        </div>
        <div class="page-header-right">
            <button type="button" class="btn btn-primary" data-edit-btn form="cfg-colegio-form">
                <i class="fa-solid fa-pen-to-square"></i>
                Editar
            </button>
            <button type="submit" class="btn btn-primary" data-edit-save form="cfg-colegio-form" hidden>
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar configuración
            </button>
            <button type="button" class="btn btn-secondary" data-edit-cancel form="cfg-colegio-form" hidden>
                <i class="fa-solid fa-xmark"></i>
                Cancelar
            </button>
        </div>
    </div>

    @include('configuracion.partials.subnav')

    <form method="POST" action="{{ route('configuracion.update') }}" id="cfg-colegio-form" data-edit-mode>
        @csrf
        @method('PUT')

        <div class="cfg-grid">

        {{-- ── Día de reporte de disponibilidad ──── --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-calendar-days" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Día de reporte de disponibilidad
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="dia_disponibilidad">
                        ¿Qué día deben reportar los árbitros su disponibilidad?
                    </label>
                    <select name="dia_disponibilidad"
                            id="dia_disponibilidad"
                            class="form-select"
                            data-nova-select
                            data-placeholder="Selecciona un día">
                        @foreach ($diasSemana as $num => $nombre)
                            <option value="{{ $num }}"
                                {{ $diaDisponibilidad === $num ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('dia_disponibilidad')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:0.75rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Los árbitros deberán reportar su disponibilidad cada
                        <strong>{{ $diasSemana[$diaDisponibilidad] }}</strong>.
                        Una vez guardada no podrán modificarla hasta el siguiente ciclo semanal.
                    </span>
                </div>

            </div>
        </div>

        {{-- ── Confirmación de designaciones ─────── --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-stopwatch" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Confirmación de designaciones
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="horas_limite_confirmacion">
                        Horas límite para confirmar
                    </label>
                    <input type="number"
                           name="horas_limite_confirmacion"
                           id="horas_limite_confirmacion"
                           class="form-input"
                           min="1"
                           max="72"
                           value="{{ old('horas_limite_confirmacion', $horasLimiteConfirmacion) }}"
                           style="max-width:140px">
                    @error('horas_limite_confirmacion')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:0.75rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        El árbitro tiene este tiempo para confirmar una designación
                        antes de que el partido pase a <strong>CRÍTICO</strong>.
                    </span>
                </div>

            </div>
        </div>

        {{-- ── Cobro automático de mensualidad ───── --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-money-check-dollar" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Cobro automático de mensualidad
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="monto_mensualidad">
                        Valor de la cuota mensual
                    </label>
                    <input type="number"
                           name="monto_mensualidad"
                           id="monto_mensualidad"
                           class="form-input"
                           min="0"
                           step="0.01"
                           value="{{ old('monto_mensualidad', $montoMensualidad) }}"
                           style="max-width:200px">
                    @error('monto_mensualidad')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="dia_vencimiento_mensualidad">
                        Día del mes en que se genera el cargo
                    </label>
                    <input type="number"
                           name="dia_vencimiento_mensualidad"
                           id="dia_vencimiento_mensualidad"
                           class="form-input"
                           min="1"
                           max="28"
                           value="{{ old('dia_vencimiento_mensualidad', $diaVencimientoMensualidad) }}"
                           style="max-width:140px">
                    @error('dia_vencimiento_mensualidad')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:0.75rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Deja el valor en <strong>0</strong> para desactivar el cobro automático — puedes
                        seguir cobrando mensualidad manualmente desde <strong>Cobro masivo</strong>.
                        Con un valor mayor a 0, cada árbitro activo recibe un cargo pendiente el día
                        configurado; el tesorero registra el pago después, como cualquier otro cargo.
                    </span>
                </div>

            </div>
        </div>

        </div>

    </form>

</div>
@endsection
