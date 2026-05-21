@extends('layouts.app')

@section('titulo', 'Nuevo Colegio')
@section('seccion', 'Colegios')

@push('styles')
    @vite(['resources/css/colegios/colegios.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Volver --}}
    <a href="{{ route('colegios.index') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width:14px;height:14px;">
            <path fill-rule="evenodd"
                  d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75
                     0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
                  clip-rule="evenodd"/>
        </svg>
        Volver a colegios
    </a>

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nuevo Colegio</h1>
            <p class="page-subheading">Completa los datos para registrar un nuevo colegio de árbitros</p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST" action="{{ route('colegios.store') }}" novalidate>
        @csrf

        <div class="form-card">

            {{-- Sección 1: Información básica --}}
            <div class="form-section">
                <p class="form-section-title">Información del colegio</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group span-2">
                        <label for="nombreColegio" class="form-label">
                            Nombre del colegio <span class="req">*</span>
                        </label>
                        <input type="text" id="nombreColegio" name="nombreColegio"
                               value="{{ old('nombreColegio') }}"
                               placeholder="Ej. Colegio de Árbitros de Cundinamarca"
                               class="form-input {{ $errors->has('nombreColegio') ? 'is-invalid' : '' }}">
                        @error('nombreColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="codigoColegio" class="form-label">
                            Código <span class="req">*</span>
                        </label>
                        <input type="text" id="codigoColegio" name="codigoColegio"
                               value="{{ old('codigoColegio') }}"
                               placeholder="Ej. CAC-001"
                               maxlength="20"
                               class="form-input {{ $errors->has('codigoColegio') ? 'is-invalid' : '' }}">
                        @error('codigoColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="emailColegio" class="form-label">
                            Correo electrónico <span class="req">*</span>
                        </label>
                        <input type="email" id="emailColegio" name="emailColegio"
                               value="{{ old('emailColegio') }}"
                               placeholder="contacto@colegio.com"
                               class="form-input {{ $errors->has('emailColegio') ? 'is-invalid' : '' }}">
                        @error('emailColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefonoColegio" class="form-label">Teléfono</label>
                        <input type="text" id="telefonoColegio" name="telefonoColegio"
                               value="{{ old('telefonoColegio') }}"
                               placeholder="Ej. 3001234567"
                               maxlength="20"
                               class="form-input {{ $errors->has('telefonoColegio') ? 'is-invalid' : '' }}">
                        @error('telefonoColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="planColegio" class="form-label">
                            Plan <span class="req">*</span>
                        </label>
                        <select id="planColegio" name="planColegio"
                                class="form-select {{ $errors->has('planColegio') ? 'is-invalid' : '' }}">
                            <option value="">Seleccionar plan…</option>
                            <option value="basico"      {{ old('planColegio') === 'basico'       ? 'selected' : '' }}>Básico</option>
                            <option value="profesional" {{ old('planColegio') === 'profesional'  ? 'selected' : '' }}>Profesional</option>
                            <option value="enterprise"  {{ old('planColegio') === 'enterprise'   ? 'selected' : '' }}>Enterprise</option>
                        </select>
                        @error('planColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="logoColegio" class="form-label">URL del logo</label>
                        <input type="url" id="logoColegio" name="logoColegio"
                               value="{{ old('logoColegio') }}"
                               placeholder="https://ejemplo.com/logo.png"
                               class="form-input {{ $errors->has('logoColegio') ? 'is-invalid' : '' }}">
                        @error('logoColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 2: Ubicación --}}
            <div class="form-section">
                <p class="form-section-title">Ubicación</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="paisColegio" class="form-label">
                            País <span class="req">*</span>
                        </label>
                        <input type="text" id="paisColegio" name="paisColegio"
                               value="{{ old('paisColegio', 'Colombia') }}"
                               placeholder="Colombia"
                               class="form-input {{ $errors->has('paisColegio') ? 'is-invalid' : '' }}">
                        @error('paisColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="departamentoColegio" class="form-label">Departamento</label>
                        <input type="text" id="departamentoColegio" name="departamentoColegio"
                               value="{{ old('departamentoColegio') }}"
                               placeholder="Ej. Cundinamarca"
                               maxlength="100"
                               class="form-input {{ $errors->has('departamentoColegio') ? 'is-invalid' : '' }}">
                        @error('departamentoColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="ciudadColegio" class="form-label">Ciudad</label>
                        <input type="text" id="ciudadColegio" name="ciudadColegio"
                               value="{{ old('ciudadColegio') }}"
                               placeholder="Ej. Bogotá"
                               maxlength="100"
                               class="form-input {{ $errors->has('ciudadColegio') ? 'is-invalid' : '' }}">
                        @error('ciudadColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="direccionColegio" class="form-label">Dirección</label>
                        <textarea id="direccionColegio" name="direccionColegio"
                                  placeholder="Calle, número, barrio…"
                                  class="form-textarea {{ $errors->has('direccionColegio') ? 'is-invalid' : '' }}">{{ old('direccionColegio') }}</textarea>
                        @error('direccionColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 3: Suscripción --}}
            <div class="form-section">
                <p class="form-section-title">Suscripción</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="fechaSuscripcion" class="form-label">Fecha de suscripción</label>
                        <input type="date" id="fechaSuscripcion" name="fechaSuscripcion"
                               value="{{ old('fechaSuscripcion') }}"
                               class="form-input {{ $errors->has('fechaSuscripcion') ? 'is-invalid' : '' }}">
                        @error('fechaSuscripcion')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="fechaExpiracion" class="form-label">Fecha de expiración</label>
                        <input type="date" id="fechaExpiracion" name="fechaExpiracion"
                               value="{{ old('fechaExpiracion') }}"
                               class="form-input {{ $errors->has('fechaExpiracion') ? 'is-invalid' : '' }}">
                        @error('fechaExpiracion')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="form-footer">
                <a href="{{ route('colegios.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                         style="width:15px;height:15px;">
                        <path fill-rule="evenodd"
                              d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75
                                 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                              clip-rule="evenodd"/>
                    </svg>
                    Guardar colegio
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/colegios/colegios.js'])
@endpush
