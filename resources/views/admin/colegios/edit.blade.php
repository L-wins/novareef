@extends('admin.layouts.app')

@section('titulo', 'Editar — ' . $colegio->nombreColegio)

@section('contenido')

{{-- Volver --}}
<a href="{{ route('admin.colegios.show', $colegio->idColegio) }}" class="admin-back-link">
    <i data-feather="arrow-left"></i>
    Volver al detalle
</a>

<div style="margin-bottom:2rem;">
    <h1 style="font-size:1.375rem;font-weight:800;color:var(--text-bright);margin:0 0 4px;letter-spacing:-0.4px;">
        Editar colegio
    </h1>
    <p style="font-size:0.875rem;color:var(--text);margin:0;font-family:monospace;">
        {{ $colegio->codigoColegio }}
    </p>
</div>

<form method="POST" action="{{ route('admin.colegios.update', $colegio->idColegio) }}" novalidate>
@csrf
@method('PUT')

{{-- Sección 1: Datos del colegio --}}
<div class="admin-form-card">
    <div class="admin-form-section">
        <p class="admin-form-section__title">Datos del colegio</p>
        <div class="admin-form-grid">

            <div class="admin-form-group admin-form-col-2">
                <label for="nombreColegio" class="admin-form-label">
                    Nombre del colegio <span class="req">*</span>
                </label>
                <input type="text" id="nombreColegio" name="nombreColegio"
                       value="{{ old('nombreColegio', $colegio->nombreColegio) }}"
                       placeholder="Ej. Colegio de Árbitros de Cundinamarca"
                       class="admin-form-input {{ $errors->has('nombreColegio') ? 'is-invalid' : '' }}"
                       autofocus>
                @error('nombreColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="codigoColegio" class="admin-form-label">
                    Código <span class="req">*</span>
                </label>
                <input type="text" id="codigoColegio" name="codigoColegio"
                       value="{{ old('codigoColegio', $colegio->codigoColegio) }}"
                       placeholder="Ej. CAC-001"
                       maxlength="20"
                       class="admin-form-input {{ $errors->has('codigoColegio') ? 'is-invalid' : '' }}"
                       style="font-family:monospace;">
                @error('codigoColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="emailColegio" class="admin-form-label">
                    Correo electrónico <span class="req">*</span>
                </label>
                <input type="email" id="emailColegio" name="emailColegio"
                       value="{{ old('emailColegio', $colegio->emailColegio) }}"
                       placeholder="contacto@colegio.com"
                       class="admin-form-input {{ $errors->has('emailColegio') ? 'is-invalid' : '' }}">
                @error('emailColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="telefonoColegio" class="admin-form-label">Teléfono</label>
                <input type="text" id="telefonoColegio" name="telefonoColegio"
                       value="{{ old('telefonoColegio', $colegio->telefonoColegio) }}"
                       placeholder="Ej. 3001234567"
                       maxlength="20"
                       class="admin-form-input {{ $errors->has('telefonoColegio') ? 'is-invalid' : '' }}">
                @error('telefonoColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="paisColegio" class="admin-form-label">
                    País <span class="req">*</span>
                </label>
                <input type="text" id="paisColegio" name="paisColegio"
                       value="{{ old('paisColegio', $colegio->paisColegio) }}"
                       placeholder="Colombia"
                       class="admin-form-input {{ $errors->has('paisColegio') ? 'is-invalid' : '' }}">
                @error('paisColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="departamentoColegio" class="admin-form-label">Departamento</label>
                <input type="text" id="departamentoColegio" name="departamentoColegio"
                       value="{{ old('departamentoColegio', $colegio->departamentoColegio) }}"
                       placeholder="Ej. Cundinamarca"
                       maxlength="100"
                       class="admin-form-input {{ $errors->has('departamentoColegio') ? 'is-invalid' : '' }}">
                @error('departamentoColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="ciudadColegio" class="admin-form-label">Ciudad</label>
                <input type="text" id="ciudadColegio" name="ciudadColegio"
                       value="{{ old('ciudadColegio', $colegio->ciudadColegio) }}"
                       placeholder="Ej. Bogotá"
                       maxlength="100"
                       class="admin-form-input {{ $errors->has('ciudadColegio') ? 'is-invalid' : '' }}">
                @error('ciudadColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group admin-form-col-2">
                <label for="direccionColegio" class="admin-form-label">Dirección</label>
                <textarea id="direccionColegio" name="direccionColegio"
                          placeholder="Calle, número, barrio…"
                          class="admin-form-textarea {{ $errors->has('direccionColegio') ? 'is-invalid' : '' }}">{{ old('direccionColegio', $colegio->direccionColegio) }}</textarea>
                @error('direccionColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group admin-form-col-2">
                <label for="logoColegio" class="admin-form-label">URL del logo</label>
                <input type="url" id="logoColegio" name="logoColegio"
                       value="{{ old('logoColegio', $colegio->logoColegio) }}"
                       placeholder="https://ejemplo.com/logo.png"
                       class="admin-form-input {{ $errors->has('logoColegio') ? 'is-invalid' : '' }}">
                @error('logoColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- Sección 2: Plan actual (solo lectura) --}}
    @php $suscripcion = $colegio->suscripcionActiva; $planActual = $suscripcion?->plan; @endphp
    <div class="admin-form-section">
        <p class="admin-form-section__title">Plan de suscripción</p>
        <div class="admin-form-grid">

            <div class="admin-form-group">
                <label class="admin-form-label">Plan activo</label>
                <div class="admin-form-readonly">
                    @if($planActual)
                        <span class="badge badge--plan-{{ strtolower($planActual->nombre) }}" style="margin-right:8px;">
                            {{ $planActual->nombre }}
                        </span>
                        ${{ number_format($planActual->precio, 0, ',', '.') }} COP / {{ $planActual->periodicidad }}
                    @else
                        <span style="color:var(--text-muted);font-style:italic;">Sin plan activo</span>
                    @endif
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Vencimiento</label>
                <div class="admin-form-readonly">
                    @if($suscripcion?->fechaVencimiento)
                        {{ $suscripcion->fechaVencimiento->format('d/m/Y') }}
                    @else
                        <span style="color:var(--text-muted);font-style:italic;">—</span>
                    @endif
                </div>
            </div>

            <div class="admin-form-group admin-form-col-2">
                <div class="admin-form-hint">
                    <i data-feather="info"></i>
                    El plan y la suscripción se gestionan desde el módulo de suscripciones.
                    Esta pantalla solo modifica los datos del colegio.
                </div>
            </div>

        </div>
    </div>

    {{-- Footer --}}
    <div class="admin-form-footer">
        <a href="{{ route('admin.colegios.show', $colegio->idColegio) }}" class="a-btn a-btn--ghost">
            Cancelar
        </a>
        <button type="submit" class="a-btn a-btn--primary">
            <i data-feather="save"></i>
            Guardar cambios
        </button>
    </div>
</div>

</form>

@endsection
