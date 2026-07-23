@extends('layouts.app')

@section('titulo', 'Editar ' . $torneo->nombreTorneo)
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
@php $modalidadBloqueada = $torneo->partidos_count > 0; @endphp

<div class="container torneo-form-page">

    <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver al torneo
    </a>

    <div class="page-header page-header--panel">
        <div class="page-header-left">
            <span class="page-kicker">Ajustes del torneo</span>
            <h1 class="page-heading">Editar torneo</h1>
            <p class="page-subheading">Datos básicos del torneo</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-note form-note--warn" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Corrige los siguientes errores:</strong>
                <ul style="margin:.4rem 0 0 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('torneos.update', $torneo->idTorneo) }}" class="form-card form-card--guided">
        @csrf
        @method('PUT')

        <div class="form-section">
            <p class="form-section-title">Datos generales</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="nombreTorneo">Nombre del torneo <span class="req">*</span></label>
                    <input type="text" id="nombreTorneo" name="nombreTorneo" maxlength="255" required
                           value="{{ old('nombreTorneo', $torneo->nombreTorneo) }}"
                           class="form-input {{ $errors->has('nombreTorneo') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="tipoTorneo">Tipo de torneo <span class="req">*</span></label>
                    <select id="tipoTorneo" name="tipoTorneo" required
                            data-nova-select data-placeholder="Selecciona el tipo"
                            class="form-select {{ $errors->has('tipoTorneo') ? 'is-invalid' : '' }}">
                        @foreach (['local' => 'Local', 'zonal' => 'Zonal', 'oficial' => 'Oficial'] as $val => $label)
                            <option value="{{ $val }}" {{ old('tipoTorneo', $torneo->tipoTorneo) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="temporada">Temporada <span class="req">*</span></label>
                    <select id="temporada" name="temporada" required
                            data-nova-select data-searchable="true" data-placeholder="Selecciona año"
                            class="form-select {{ $errors->has('temporada') ? 'is-invalid' : '' }}">
                        @for ($y = 2035; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ (int) old('temporada', $torneo->temporada) === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Modalidad de pago</p>
            @if ($modalidadBloqueada)
                <div class="form-note form-note--warn">
                    <i class="fa-solid fa-lock"></i>
                    <div>
                        La modalidad de pago está bloqueada porque ya hay
                        <strong>{{ $torneo->partidos_count }} partido{{ $torneo->partidos_count === 1 ? '' : 's' }}</strong>
                        registrado{{ $torneo->partidos_count === 1 ? '' : 's' }}.
                        Modalidad actual: <strong>{{ $torneo->modalidadPago === 'campo' ? 'Pago en campo' : 'Por nómina' }}</strong>.
                    </div>
                </div>
            @else
                <div class="form-radio-group">
                    <label class="form-radio-card">
                        <input type="radio" name="modalidadPago" value="campo" {{ old('modalidadPago', $torneo->modalidadPago) === 'campo' ? 'checked' : '' }}>
                        <div>
                            Pago en campo
                            <small>El árbitro recibe el pago el día del partido.</small>
                        </div>
                    </label>
                    <label class="form-radio-card">
                        <input type="radio" name="modalidadPago" value="nomina" {{ old('modalidadPago', $torneo->modalidadPago) === 'nomina' ? 'checked' : '' }}>
                        <div>
                            Por nómina
                            <small>El colegio liquida los pagos al final del periodo.</small>
                        </div>
                    </label>
                </div>
            @endif
        </div>

        <div class="form-section">
            <p class="form-section-title">Organizador</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="organizadorNombre">Nombre <span class="req">*</span></label>
                    <input type="text" id="organizadorNombre" name="organizadorNombre" maxlength="150" required
                           value="{{ old('organizadorNombre', $torneo->organizadorNombre) }}"
                           class="form-input {{ $errors->has('organizadorNombre') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="organizadorTelefono">Teléfono</label>
                    <input type="text" id="organizadorTelefono" name="organizadorTelefono" maxlength="20"
                           value="{{ old('organizadorTelefono', $torneo->organizadorTelefono) }}"
                           class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label" for="organizadorEmail">Correo electrónico</label>
                    <input type="email" id="organizadorEmail" name="organizadorEmail" maxlength="255"
                           value="{{ old('organizadorEmail', $torneo->organizadorEmail) }}"
                           class="form-input {{ $errors->has('organizadorEmail') ? 'is-invalid' : '' }}">
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Periodo</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fechaInicio">Inicio <span class="req">*</span></label>
                    <input type="text" id="fechaInicio" name="fechaInicio" required
                           data-nova-date placeholder="dd/mm/aaaa"
                           value="{{ old('fechaInicio', $torneo->fechaInicio->format('Y-m-d')) }}"
                           class="form-input {{ $errors->has('fechaInicio') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="fechaFin">Fin <span class="req">*</span></label>
                    <input type="text" id="fechaFin" name="fechaFin" required
                           data-nova-date placeholder="dd/mm/aaaa"
                           value="{{ old('fechaFin', $torneo->fechaFin->format('Y-m-d')) }}"
                           class="form-input {{ $errors->has('fechaFin') ? 'is-invalid' : '' }}">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
@endpush
