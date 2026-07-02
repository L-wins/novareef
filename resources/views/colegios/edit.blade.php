@extends('layouts.app')

@section('titulo', 'Editar — ' . $colegio->nombreColegio)
@section('seccion', 'Colegios')

@push('styles')
    @vite(['resources/css/colegios/colegios.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Volver --}}
    <a href="{{ route('colegios.show', $colegio->idColegio) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver al detalle
    </a>

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Editar Colegio</h1>
            <p class="page-subheading" style="font-family:'Cascadia Code','SF Mono',monospace;font-size:0.8rem;">
                {{ $colegio->codigoColegio }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST"
          action="{{ route('colegios.update', $colegio->idColegio) }}"
          novalidate>
        @csrf
        @method('PUT')

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
                               value="{{ old('nombreColegio', $colegio->nombreColegio) }}"
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
                               value="{{ old('codigoColegio', $colegio->codigoColegio) }}"
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
                               value="{{ old('emailColegio', $colegio->emailColegio) }}"
                               placeholder="contacto@colegio.com"
                               class="form-input {{ $errors->has('emailColegio') ? 'is-invalid' : '' }}">
                        @error('emailColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefonoColegio" class="form-label">Teléfono</label>
                        <input type="text" id="telefonoColegio" name="telefonoColegio"
                               value="{{ old('telefonoColegio', $colegio->telefonoColegio) }}"
                               placeholder="Ej. 3001234567"
                               maxlength="20"
                               class="form-input {{ $errors->has('telefonoColegio') ? 'is-invalid' : '' }}">
                        @error('telefonoColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="logoColegio" class="form-label">URL del logo</label>
                        <input type="url" id="logoColegio" name="logoColegio"
                               value="{{ old('logoColegio', $colegio->logoColegio) }}"
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
                               value="{{ old('paisColegio', $colegio->paisColegio) }}"
                               placeholder="Colombia"
                               class="form-input {{ $errors->has('paisColegio') ? 'is-invalid' : '' }}">
                        @error('paisColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="departamentoColegio" class="form-label">Departamento</label>
                        <input type="text" id="departamentoColegio" name="departamentoColegio"
                               value="{{ old('departamentoColegio', $colegio->departamentoColegio) }}"
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
                               value="{{ old('ciudadColegio', $colegio->ciudadColegio) }}"
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
                                  class="form-textarea {{ $errors->has('direccionColegio') ? 'is-invalid' : '' }}">{{ old('direccionColegio', $colegio->direccionColegio) }}</textarea>
                        @error('direccionColegio')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 3: Plan de suscripción (solo lectura) --}}
            <div class="form-section">
                <p class="form-section-title">Plan de suscripción</p>
                <div class="form-grid form-grid-2">

                    @php
                        $suscripcion = $colegio->suscripcionActiva;
                        $planActual  = $suscripcion?->plan;
                    @endphp

                    <div class="form-group">
                        <label class="form-label">Plan activo</label>
                        <div class="form-input" style="background:rgba(255,255,255,0.03);cursor:default;user-select:none;">
                            @if ($planActual)
                                {{ $planActual->nombre }}
                                — ${{ number_format($planActual->precio, 0, ',', '.') }} COP / {{ $planActual->periodicidad }}
                            @else
                                Sin plan activo
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vencimiento</label>
                        <div class="form-input" style="background:rgba(255,255,255,0.03);cursor:default;user-select:none;">
                            @if ($suscripcion)
                                {{ $suscripcion->fechaVencimiento->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="form-group span-2">
                        <p class="form-hint">
                            Para cambiar el plan o renovar la suscripción, gestiona la suscripción
                            directamente desde el módulo de suscripciones.
                        </p>
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="form-footer">
                <a href="{{ route('colegios.show', $colegio->idColegio) }}"
                   class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/colegios/colegios.js'])
@endpush
