@extends('admin.layouts.app')

@section('titulo', 'Editar plan — ' . $plan->nombre)

@section('contenido')

@php
    $modulos = ['arbitros', 'torneos', 'designaciones', 'finanzas', 'academico', 'sanciones', 'reportes'];
    $modulosActivos = $plan->modulosJSON ?? [];
@endphp

{{-- Volver --}}
<a href="{{ route('admin.planes.show', $plan->idPlan) }}" class="admin-back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Volver al plan
</a>

<div class="admin-page-header">
    <h1>Editar plan</h1>
    <p>Modifica la configuración de <strong>{{ $plan->nombre }}</strong>.</p>
</div>

@if($errors->any())
<div class="admin-alert admin-alert--danger mb-4">
    <i class="fa-solid fa-circle-exclamation"></i>
    <div>
        <strong>Corrige los siguientes errores:</strong>
        <ul class="admin-alert__list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.planes.update', $plan->idPlan) }}" novalidate>
    @csrf
    @method('PUT')

    {{-- Datos básicos --}}
    <div class="admin-detail-card">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Datos básicos</p>
            <div class="admin-detail-grid">

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
                    <select id="periodicidad" name="periodicidad" data-nova-select
                            class="{{ $errors->has('periodicidad') ? 'admin-input--error' : '' }}" required>
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

                {{-- Límite cuentas admin --}}
                <div class="admin-form-group">
                    <label class="admin-form-label" for="limiteCuentasAdmin">
                        Límite cuentas admin
                        <span class="admin-form-hint">Dejar vacío = ilimitado</span>
                    </label>
                    <input type="number" id="limiteCuentasAdmin" name="limiteCuentasAdmin"
                           class="admin-input {{ $errors->has('limiteCuentasAdmin') ? 'admin-input--error' : '' }}"
                           value="{{ old('limiteCuentasAdmin', $plan->limiteCuentasAdmin) }}" min="1" placeholder="Ilimitado">
                    @error('limiteCuentasAdmin')
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
    <div class="admin-detail-card">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Módulos habilitados</p>
            <div class="plan-edit-modulos">
                @foreach($modulos as $modulo)
                @php $checked = in_array($modulo, old('modulos', $modulosActivos)); @endphp
                <label class="plan-edit-modulo-label {{ $checked ? 'plan-edit-modulo-label--active' : '' }}" id="lbl-{{ $modulo }}">
                    <input type="checkbox" name="modulos[]" value="{{ $modulo }}"
                           {{ $checked ? 'checked' : '' }}
                           onchange="this.closest('label').classList.toggle('plan-edit-modulo-label--active', this.checked)">
                    <i class="fa-solid {{ match($modulo) {
                        'arbitros'      => 'fa-users',
                        'torneos'       => 'fa-trophy',
                        'designaciones' => 'fa-clipboard',
                        'finanzas'      => 'fa-dollar-sign',
                        'academico'     => 'fa-book-open',
                        'sanciones'     => 'fa-shield-halved',
                        'reportes'      => 'fa-chart-bar',
                        default         => 'fa-circle',
                    } }}"></i>
                    {{ ucfirst($modulo) }}
                </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Opciones adicionales --}}
    <div class="admin-detail-card">
        <div class="admin-detail-section">
            <p class="admin-detail-section__title">Opciones adicionales</p>
            <div class="plan-edit-toggles">

                <label class="plan-edit-toggle-label">
                    <div class="plan-edit-toggle-wrap">
                        <input type="hidden" name="incluyePaginaWeb" value="0">
                        <input type="checkbox" name="incluyePaginaWeb" value="1" class="plan-edit-toggle"
                               {{ old('incluyePaginaWeb', $plan->incluyePaginaWeb) ? 'checked' : '' }}>
                        <span class="plan-edit-toggle-slider"></span>
                    </div>
                    <span class="plan-edit-toggle-text">
                        <span class="plan-edit-toggle-title">Incluye página web</span>
                        <span class="plan-edit-toggle-desc">El colegio recibe un subdominio en novareef.com</span>
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
                        <span class="plan-edit-toggle-title">Incluye onboarding</span>
                        <span class="plan-edit-toggle-desc">Sesión de configuración asistida por el equipo NovaReef</span>
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
                        <span class="plan-edit-toggle-title">Visible en la página pública</span>
                        <span class="plan-edit-toggle-desc">Los colegios pueden ver este plan en el landing</span>
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
                        <span class="plan-edit-toggle-title">Plan activo</span>
                        <span class="plan-edit-toggle-desc">Los colegios pueden suscribirse a este plan</span>
                    </span>
                </label>

            </div>
        </div>
    </div>

    {{-- Botones --}}
    <div class="admin-form-actions">
        <a href="{{ route('admin.planes.show', $plan->idPlan) }}" class="a-btn a-btn--ghost">
            Cancelar
        </a>
        <button type="submit" class="a-btn a-btn--primary">
            <i class="fa-solid fa-floppy-disk"></i>
            Guardar cambios
        </button>
    </div>

</form>

@endsection
