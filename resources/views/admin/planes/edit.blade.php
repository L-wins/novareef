@extends('admin.layouts.app')

@section('titulo', 'Editar plan — ' . $plan->nombre)

@section('contenido')

@php
    $modulos = ['arbitros', 'torneos', 'designaciones', 'finanzas', 'academico', 'sanciones', 'reportes'];
    $modulosActivos = $plan->modulosJSON ?? [];
@endphp

{{-- Volver --}}
<a href="{{ route('admin.planes.show', $plan->idPlan) }}" class="admin-back-link">
    <i data-feather="arrow-left"></i>
    Volver al plan
</a>

<div class="admin-page-header" style="margin-bottom:1.5rem;">
    <h1>Editar plan</h1>
    <p>Modifica la configuración de <strong style="color:var(--text-bright);">{{ $plan->nombre }}</strong>.</p>
</div>

@if($errors->any())
<div class="admin-alert admin-alert--danger" style="margin-bottom:1.25rem;">
    <i data-feather="alert-circle"></i>
    <div>
        <strong>Corrige los siguientes errores:</strong>
        <ul style="margin:6px 0 0 16px;padding:0;list-style:disc;">
            @foreach($errors->all() as $error)
                <li style="font-size:0.8125rem;margin-top:2px;">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.planes.update', $plan->idPlan) }}" novalidate>
    @csrf
    @method('PUT')

    {{-- Datos básicos --}}
    <div class="admin-detail-card" style="margin-bottom:1.25rem;">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Datos básicos</p>
            <div class="admin-detail-grid" style="row-gap:1.25rem;">

                {{-- Nombre --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="nombre">Nombre del plan</label>
                    <input type="text" id="nombre" name="nombre" class="admin-input {{ $errors->has('nombre') ? 'admin-input--error' : '' }}"
                           value="{{ old('nombre', $plan->nombre) }}" placeholder="ej. Goliath" maxlength="100" required>
                    @error('nombre')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Precio --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="precio">Precio (COP)</label>
                    <input type="number" id="precio" name="precio" class="admin-input {{ $errors->has('precio') ? 'admin-input--error' : '' }}"
                           value="{{ old('precio', $plan->precio) }}" min="0" step="1000" required>
                    @error('precio')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Periodicidad --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="periodicidad">Periodicidad</label>
                    <select id="periodicidad" name="periodicidad" class="admin-input {{ $errors->has('periodicidad') ? 'admin-input--error' : '' }}" required>
                        <option value="mensual" {{ old('periodicidad', $plan->periodicidad) === 'mensual' ? 'selected' : '' }}>Mensual</option>
                        <option value="anual"   {{ old('periodicidad', $plan->periodicidad) === 'anual'   ? 'selected' : '' }}>Anual</option>
                    </select>
                    @error('periodicidad')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Límite árbitros --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="limiteArbitros">
                        Límite árbitros
                        <span class="admin-form-hint">Dejar vacío = ilimitado</span>
                    </label>
                    <input type="number" id="limiteArbitros" name="limiteArbitros"
                           class="admin-input {{ $errors->has('limiteArbitros') ? 'admin-input--error' : '' }}"
                           value="{{ old('limiteArbitros', $plan->limiteArbitros) }}" min="1" placeholder="Ilimitado">
                    @error('limiteArbitros')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Límite roles --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="limiteRoles">
                        Límite roles
                        <span class="admin-form-hint">Dejar vacío = ilimitado</span>
                    </label>
                    <input type="number" id="limiteRoles" name="limiteRoles"
                           class="admin-input {{ $errors->has('limiteRoles') ? 'admin-input--error' : '' }}"
                           value="{{ old('limiteRoles', $plan->limiteRoles) }}" min="1" placeholder="Ilimitado">
                    @error('limiteRoles')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Orden --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="orden">Orden de visualización</label>
                    <input type="number" id="orden" name="orden"
                           class="admin-input {{ $errors->has('orden') ? 'admin-input--error' : '' }}"
                           value="{{ old('orden', $plan->orden) }}" min="0" required>
                    @error('orden')
                        <p class="admin-form-error">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Módulos --}}
    <div class="admin-detail-card" style="margin-bottom:1.25rem;">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Módulos habilitados</p>
            <div class="plan-edit-modulos">
                @foreach($modulos as $modulo)
                @php $checked = in_array($modulo, old('modulos', $modulosActivos)); @endphp
                <label class="plan-edit-modulo-label {{ $checked ? 'plan-edit-modulo-label--active' : '' }}" id="lbl-{{ $modulo }}">
                    <input type="checkbox" name="modulos[]" value="{{ $modulo }}"
                           {{ $checked ? 'checked' : '' }}
                           onchange="this.closest('label').classList.toggle('plan-edit-modulo-label--active', this.checked)">
                    <i data-feather="{{ match($modulo) {
                        'arbitros'      => 'users',
                        'torneos'       => 'award',
                        'designaciones' => 'clipboard',
                        'finanzas'      => 'dollar-sign',
                        'academico'     => 'book-open',
                        'sanciones'     => 'shield',
                        'reportes'      => 'bar-chart-2',
                        default         => 'circle',
                    } }}"></i>
                    {{ ucfirst($modulo) }}
                </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Opciones adicionales --}}
    <div class="admin-detail-card" style="margin-bottom:1.25rem;">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Opciones adicionales</p>
            <div style="display:flex;flex-direction:column;gap:1rem;">

                <label class="plan-edit-toggle-label">
                    <div class="plan-edit-toggle-wrap">
                        <input type="hidden" name="incluyePaginaWeb" value="0">
                        <input type="checkbox" name="incluyePaginaWeb" value="1" class="plan-edit-toggle"
                               {{ old('incluyePaginaWeb', $plan->incluyePaginaWeb) ? 'checked' : '' }}>
                        <span class="plan-edit-toggle-slider"></span>
                    </div>
                    <span class="plan-edit-toggle-text">
                        <span style="color:var(--text-bright);font-weight:500;">Incluye página web</span>
                        <span style="font-size:0.75rem;color:var(--text-muted);">El colegio recibe un subdominio en novareef.com</span>
                    </span>
                </label>

                <label class="plan-edit-toggle-label">
                    <div class="plan-edit-toggle-wrap">
                        <input type="hidden" name="incluyeOnboarding" value="0">
                        <input type="checkbox" name="incluyeOnboarding" value="1" class="plan-edit-toggle"
                               {{ old('incluyeOnboarding', $plan->incluyeOnboarding) ? 'checked' : '' }}>
                        <span class="plan-edit-toggle-slider"></span>
                    </div>
                    <span class="plan-edit-toggle-text">
                        <span style="color:var(--text-bright);font-weight:500;">Incluye onboarding</span>
                        <span style="font-size:0.75rem;color:var(--text-muted);">Sesión de configuración asistida por el equipo NovaReef</span>
                    </span>
                </label>

                <label class="plan-edit-toggle-label">
                    <div class="plan-edit-toggle-wrap">
                        <input type="hidden" name="esVisible" value="0">
                        <input type="checkbox" name="esVisible" value="1" class="plan-edit-toggle"
                               {{ old('esVisible', $plan->esVisible) ? 'checked' : '' }}>
                        <span class="plan-edit-toggle-slider"></span>
                    </div>
                    <span class="plan-edit-toggle-text">
                        <span style="color:var(--text-bright);font-weight:500;">Visible en la página pública</span>
                        <span style="font-size:0.75rem;color:var(--text-muted);">Los colegios pueden ver este plan en el landing</span>
                    </span>
                </label>

                <label class="plan-edit-toggle-label">
                    <div class="plan-edit-toggle-wrap">
                        <input type="hidden" name="esActivo" value="0">
                        <input type="checkbox" name="esActivo" value="1" class="plan-edit-toggle"
                               {{ old('esActivo', $plan->esActivo) ? 'checked' : '' }}>
                        <span class="plan-edit-toggle-slider"></span>
                    </div>
                    <span class="plan-edit-toggle-text">
                        <span style="color:var(--text-bright);font-weight:500;">Plan activo</span>
                        <span style="font-size:0.75rem;color:var(--text-muted);">Los colegios pueden suscribirse a este plan</span>
                    </span>
                </label>

            </div>
        </div>
    </div>

    {{-- Botones --}}
    <div style="display:flex;align-items:center;gap:10px;justify-content:flex-end;">
        <a href="{{ route('admin.planes.show', $plan->idPlan) }}" class="a-btn a-btn--ghost">
            Cancelar
        </a>
        <button type="submit" class="a-btn a-btn--primary">
            <i data-feather="save"></i>
            Guardar cambios
        </button>
    </div>

</form>

@endsection
