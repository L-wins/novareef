@extends('admin.layouts.app')

@section('titulo', 'Configuración 2FA')

@section('contenido')

<div class="admin-page-header">
    <h1>Autenticación de dos factores</h1>
    <p>Añade una capa de seguridad adicional a tu cuenta de administrador.</p>
</div>

@if($activo)

    {{-- ══ 2FA ACTIVO ══ --}}
    <div class="twofa-active-wrap">

        <div class="twofa-active-head">
            <div class="twofa-active-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="twofa-active-badge">
                    <span class="twofa-dot"></span>
                    2FA Activo
                </div>
                <p class="twofa-active-desc">
                    Tu cuenta está protegida con autenticación de dos factores.
                </p>
            </div>
        </div>

        <hr class="twofa-divider">

        <p class="twofa-disable-note">
            Para desactivar el 2FA confirma con tu contraseña actual.
        </p>

        <form method="POST" action="{{ route('admin.2fa.disable') }}" class="twofa-disable-form">
            @csrf

            <div class="twofa-disable-form__field">
                <div class="a-input-wrap">
                    <span class="a-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password"
                           placeholder="Contraseña actual"
                           class="a-input {{ $errors->has('password') ? 'is-invalid' : '' }}">
                </div>
                @error('password')
                    <p class="a-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="a-btn a-btn--danger-soft twofa-disable-form__btn">
                <i class="fa-solid fa-circle-xmark"></i>
                Desactivar 2FA
            </button>
        </form>

    </div>

@else

    {{-- ══ 2FA INACTIVO — ACTIVAR ══ --}}
    <div class="twofa-grid">

        {{-- Panel QR --}}
        <div class="twofa-panel">
            <h3>1. Escanea el código QR</h3>
            <p>Abre Google Authenticator u otra app TOTP y escanea este código.</p>

            <div class="qr-box">
                {!! $qrSvg !!}
            </div>

            <p class="twofa-hint">
                ¿No puedes escanear? Ingresa esta clave manualmente:
            </p>
            <div class="secret-code">{{ $secret }}</div>
        </div>

        {{-- Panel instrucciones + formulario --}}
        <div class="twofa-panel twofa-panel--column">
            <h3>2. Verifica e ingresa el código</h3>
            <p>Ingresa el código de 6 dígitos que muestra la app para confirmar la activación.</p>

            <ol class="step-list">
                <li class="step-item">
                    <span class="step-num">1</span>
                    <span>Descarga <strong>Google Authenticator</strong> o cualquier app TOTP compatible.</span>
                </li>
                <li class="step-item">
                    <span class="step-num">2</span>
                    <span>Escanea el código QR de la izquierda con la app.</span>
                </li>
                <li class="step-item">
                    <span class="step-num">3</span>
                    <span>Escribe el <strong>código de 6 dígitos</strong> que aparece en la app.</span>
                </li>
            </ol>

            <form method="POST" action="{{ route('admin.2fa.enable') }}" class="twofa-enable-form">
                @csrf

                <p class="twofa-code-label">Código de verificación</p>

                <input type="hidden" name="codigo" id="otp-code">
                <div class="otp-row otp-row--left {{ $errors->has('codigo') ? 'otp-has-error' : '' }}">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" class="otp-digit"
                               inputmode="numeric" pattern="[0-9]"
                               maxlength="1" aria-label="Dígito {{ $i + 1 }}">
                    @endfor
                </div>

                @error('codigo')
                    <p class="a-field-error twofa-code-error">{{ $message }}</p>
                @else
                    <p class="twofa-hint twofa-hint--spaced">
                        El código cambia cada 30 segundos.
                    </p>
                @enderror

                <button type="submit" class="a-btn a-btn--primary a-btn--full">
                    <i class="fa-solid fa-shield-halved"></i>
                    Activar 2FA
                </button>
            </form>
        </div>

    </div>

    {{-- Advertencia --}}
    <div class="admin-flash admin-flash--warning mt-4">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Guarda la clave secreta en un lugar seguro. Sin ella no podrás recuperar el acceso si pierdes tu dispositivo.
    </div>

@endif

@endsection
