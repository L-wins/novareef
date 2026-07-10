@extends('admin.layouts.app')

@section('titulo', 'Nuevo colegio')

@section('contenido')

{{-- Volver --}}
<a href="{{ route('admin.colegios.index') }}" class="admin-back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Volver a colegios
</a>

<div class="admin-page-header">
    <h1>Nuevo colegio</h1>
    <p>Completa los datos para registrar un nuevo colegio de árbitros en la plataforma.</p>
</div>

<form method="POST" action="{{ route('admin.colegios.store') }}" novalidate>
@csrf

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
                       value="{{ old('nombreColegio') }}"
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
                       value="{{ old('codigoColegio') }}"
                       placeholder="Ej. CAC-001"
                       maxlength="20"
                       class="admin-form-input admin-form-input--mono {{ $errors->has('codigoColegio') ? 'is-invalid' : '' }}">
                @error('codigoColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="emailColegio" class="admin-form-label">
                    Correo electrónico <span class="req">*</span>
                </label>
                <input type="email" id="emailColegio" name="emailColegio"
                       value="{{ old('emailColegio') }}"
                       placeholder="contacto@colegio.com"
                       class="admin-form-input {{ $errors->has('emailColegio') ? 'is-invalid' : '' }}">
                @error('emailColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="telefonoColegio" class="admin-form-label">Teléfono</label>
                <input type="text" id="telefonoColegio" name="telefonoColegio"
                       value="{{ old('telefonoColegio') }}"
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
                       value="{{ old('paisColegio', 'Colombia') }}"
                       placeholder="Colombia"
                       class="admin-form-input {{ $errors->has('paisColegio') ? 'is-invalid' : '' }}">
                @error('paisColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="departamentoColegio" class="admin-form-label">Departamento</label>
                <input type="text" id="departamentoColegio" name="departamentoColegio"
                       value="{{ old('departamentoColegio') }}"
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
                       value="{{ old('ciudadColegio') }}"
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
                          class="admin-form-textarea {{ $errors->has('direccionColegio') ? 'is-invalid' : '' }}">{{ old('direccionColegio') }}</textarea>
                @error('direccionColegio')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- Sección 2: Plan de suscripción --}}
    <div class="admin-form-section">
        <p class="admin-form-section__title">Plan de suscripción</p>

        @error('idPlan')
            <div class="admin-flash admin-flash--danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $message }}
            </div>
        @enderror

        <div class="plan-cards-grid">
            @foreach($planes as $plan)
            @php $planKey = strtolower($plan->nombre); @endphp
            <label class="plan-card {{ old('idPlan') == $plan->idPlan ? 'plan-card--selected' : '' }}"
                   data-plan="{{ $planKey }}"
                   id="plan-label-{{ $plan->idPlan }}">
                <input type="radio" name="idPlan" value="{{ $plan->idPlan }}"
                       {{ old('idPlan') == $plan->idPlan ? 'checked' : '' }}>

                <div class="plan-card-check">
                    <i class="fa-solid fa-check"></i>
                </div>

                <span class="plan-card__badge">{{ $plan->nombre }}</span>

                <div class="plan-card__price">
                    ${{ number_format($plan->precio, 0, ',', '.') }}
                    <span>COP / {{ $plan->periodicidad }}</span>
                </div>

                <div class="plan-card__detail">
                    <i class="fa-solid fa-users"></i>
                    {{ $plan->limiteArbitrosTexto }} árbitros
                </div>

                @if($plan->incluyePaginaWeb)
                <div class="plan-card__detail">
                    <i class="fa-solid fa-globe"></i>
                    Página web
                </div>
                @endif

                @if($plan->incluyeOnboarding)
                <div class="plan-card__detail">
                    <i class="fa-solid fa-life-ring"></i>
                    Onboarding
                </div>
                @endif
            </label>
            @endforeach
        </div>
    </div>

    {{-- Sección 3: Administrador --}}
    <div class="admin-form-section">
        <p class="admin-form-section__title">Administrador del colegio</p>
        <div class="admin-form-grid">

            <div class="admin-form-group">
                <label for="nombreAdmin" class="admin-form-label">
                    Nombre completo <span class="req">*</span>
                </label>
                <input type="text" id="nombreAdmin" name="nombreAdmin"
                       value="{{ old('nombreAdmin') }}"
                       placeholder="Ej. Juan Carlos Pérez"
                       maxlength="150"
                       class="admin-form-input {{ $errors->has('nombreAdmin') ? 'is-invalid' : '' }}">
                @error('nombreAdmin')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group">
                <label for="emailAdmin" class="admin-form-label">
                    Correo electrónico <span class="req">*</span>
                </label>
                <input type="email" id="emailAdmin" name="emailAdmin"
                       value="{{ old('emailAdmin') }}"
                       placeholder="admin@colegio.com"
                       class="admin-form-input {{ $errors->has('emailAdmin') ? 'is-invalid' : '' }}">
                @error('emailAdmin')
                    <p class="admin-form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="admin-form-group admin-form-col-2">
                <div class="admin-form-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    Se generará una contraseña automática y se enviará al correo ingresado.
                    El administrador deberá cambiarla en su primer inicio de sesión.
                </div>
            </div>

        </div>
    </div>

    {{-- Footer --}}
    <div class="admin-form-footer">
        <a href="{{ route('admin.colegios.index') }}" class="a-btn a-btn--ghost">
            Cancelar
        </a>
        <button type="submit" class="a-btn a-btn--primary">
            <i class="fa-solid fa-check"></i>
            Crear colegio
        </button>
    </div>
</div>

</form>

{{-- La selección visual de plan-card vive en admin.js (regla de separación de capas) --}}

@endsection
