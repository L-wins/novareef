@extends('layouts.app')

@section('titulo', 'Nuevo torneo')
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container torneo-form-page">

    <a href="{{ route('torneos.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a torneos
    </a>

    <div class="page-header page-header--panel">
        <div class="page-header-left">
            <span class="page-kicker">Configuración inicial</span>
            <h1 class="page-heading">Nuevo torneo</h1>
            <p class="page-subheading">Paso 1 de 2 — datos básicos</p>
        </div>
    </div>

    <div class="form-note form-note--intro">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            Después podrás agregar <strong>divisiones</strong>, <strong>sedes</strong>,
            <strong>tarifas</strong> por rol y formato, y el <strong>reglamento PDF</strong> en el perfil del torneo.
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

    <form method="POST" action="{{ route('torneos.store') }}" class="form-card form-card--guided">
        @csrf

        {{-- DATOS GENERALES --}}
        <div class="form-section">
            <p class="form-section-title">Datos generales</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="nombreTorneo">Nombre del torneo <span class="req">*</span></label>
                    <input type="text" id="nombreTorneo" name="nombreTorneo" maxlength="255" required
                           value="{{ old('nombreTorneo') }}"
                           placeholder="Ej. Copa Antioquia 2026"
                           class="form-input {{ $errors->has('nombreTorneo') ? 'is-invalid' : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="tipoTorneo">Tipo de torneo <span class="req">*</span></label>
                    <select id="tipoTorneo" name="tipoTorneo" required
                            data-nova-select data-placeholder="Selecciona el tipo"
                            class="form-select {{ $errors->has('tipoTorneo') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach (['local' => 'Local', 'zonal' => 'Zonal', 'oficial' => 'Oficial'] as $val => $label)
                            <option value="{{ $val }}" {{ old('tipoTorneo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="temporada">Temporada <span class="req">*</span></label>
                    <select id="temporada" name="temporada" required
                            data-nova-select data-searchable="true" data-placeholder="Selecciona año"
                            class="form-select {{ $errors->has('temporada') ? 'is-invalid' : '' }}">
                        @for ($y = 2035; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ (int) old('temporada', date('Y')) === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- MODALIDAD DE PAGO --}}
        <div class="form-section">
            <p class="form-section-title">Modalidad de pago</p>
            <div class="form-radio-group">
                <label class="form-radio-card">
                    <input type="radio" name="modalidadPago" value="campo" {{ old('modalidadPago', 'campo') === 'campo' ? 'checked' : '' }}>
                    <div>
                        Pago en campo
                        <small>El árbitro recibe el pago directamente el día del partido.</small>
                    </div>
                </label>
                <label class="form-radio-card">
                    <input type="radio" name="modalidadPago" value="nomina" {{ old('modalidadPago') === 'nomina' ? 'checked' : '' }}>
                    <div>
                        Por nómina
                        <small>El colegio liquida los pagos por nómina al final del periodo.</small>
                    </div>
                </label>
            </div>
            <div class="form-note form-note--warn" style="margin-top:0.85rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>Esta modalidad <strong>no podrá cambiarse</strong> una vez haya partidos registrados.</div>
            </div>
        </div>

        {{-- ORGANIZADOR --}}
        <div class="form-section">
            <p class="form-section-title">Organizador del torneo</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="organizadorNombre">Nombre del organizador <span class="req">*</span></label>
                    <input type="text" id="organizadorNombre" name="organizadorNombre" maxlength="150" required
                           value="{{ old('organizadorNombre') }}"
                           placeholder="Ej. Liga Antioqueña de Fútbol"
                           class="form-input {{ $errors->has('organizadorNombre') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="organizadorTelefono">Teléfono</label>
                    <input type="text" id="organizadorTelefono" name="organizadorTelefono" maxlength="20"
                           value="{{ old('organizadorTelefono') }}"
                           class="form-input {{ $errors->has('organizadorTelefono') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="organizadorEmail">Correo electrónico</label>
                    <input type="email" id="organizadorEmail" name="organizadorEmail" maxlength="255"
                           value="{{ old('organizadorEmail') }}"
                           class="form-input {{ $errors->has('organizadorEmail') ? 'is-invalid' : '' }}">
                </div>
            </div>
        </div>

        {{-- FECHAS --}}
        <div class="form-section">
            <p class="form-section-title">Periodo del torneo</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fechaInicio">Fecha de inicio <span class="req">*</span></label>
                    <input type="text" id="fechaInicio" name="fechaInicio" required
                           data-nova-date placeholder="dd/mm/aaaa"
                           value="{{ old('fechaInicio') }}"
                           class="form-input {{ $errors->has('fechaInicio') ? 'is-invalid' : '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="fechaFin">Fecha de fin <span class="req">*</span></label>
                    <input type="text" id="fechaFin" name="fechaFin" required
                           data-nova-date placeholder="dd/mm/aaaa"
                           value="{{ old('fechaFin') }}"
                           class="form-input {{ $errors->has('fechaFin') ? 'is-invalid' : '' }}">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('torneos.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                Crear torneo y continuar
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
@endpush
