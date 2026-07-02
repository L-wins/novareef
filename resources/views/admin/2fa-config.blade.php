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

        <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:2rem;">
            <div style="width:56px;height:56px;border-radius:16px;background:rgba(16,185,129,0.1);
                        border:1px solid rgba(16,185,129,0.2);
                        display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-shield-halved" style="font-size:26px;color:var(--success);"></i>
            </div>
            <div>
                <div class="twofa-active-badge">
                    <span class="twofa-dot"></span>
                    2FA Activo
                </div>
                <p style="margin:6px 0 0;font-size:0.875rem;color:var(--text);">
                    Tu cuenta está protegida con autenticación de dos factores.
                </p>
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border-color);margin-bottom:1.5rem;">

        <p style="font-size:0.875rem;color:var(--text);margin:0 0 1rem;">
            Para desactivar el 2FA confirma con tu contraseña actual.
        </p>

        <form method="POST" action="{{ route('admin.2fa.disable') }}"
              style="display:flex;gap:0.75rem;align-items:flex-start;max-width:420px;">
            @csrf

            <div style="flex:1;">
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

            <button type="submit" class="a-btn a-btn--danger-soft" style="white-space:nowrap;">
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

            <p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 6px;">
                ¿No puedes escanear? Ingresa esta clave manualmente:
            </p>
            <div class="secret-code">{{ $secret }}</div>
        </div>

        {{-- Panel instrucciones + formulario --}}
        <div class="twofa-panel" style="display:flex;flex-direction:column;">
            <h3>2. Verifica e ingresa el código</h3>
            <p>Ingresa el código de 6 dígitos que muestra la app para confirmar la activación.</p>

            <ol class="step-list">
                <li class="step-item">
                    <span class="step-num">1</span>
                    <span>Descarga <strong style="color:var(--text-bright);">Google Authenticator</strong> o cualquier app TOTP compatible.</span>
                </li>
                <li class="step-item">
                    <span class="step-num">2</span>
                    <span>Escanea el código QR de la izquierda con la app.</span>
                </li>
                <li class="step-item">
                    <span class="step-num">3</span>
                    <span>Escribe el <strong style="color:var(--text-bright);">código de 6 dígitos</strong> que aparece en la app.</span>
                </li>
            </ol>

            <form method="POST" action="{{ route('admin.2fa.enable') }}" style="margin-top:auto;">
                @csrf

                <p style="font-size:0.8125rem;color:var(--text);margin:0 0 8px;">Código de verificación</p>

                <input type="hidden" name="codigo" id="otp-code">
                <div class="otp-row {{ $errors->has('codigo') ? 'otp-has-error' : '' }}"
                     style="justify-content:flex-start;margin-bottom:8px;">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" class="otp-digit"
                               inputmode="numeric" pattern="[0-9]"
                               maxlength="1" aria-label="Dígito {{ $i + 1 }}">
                    @endfor
                </div>

                @error('codigo')
                    <p class="a-field-error" style="margin-bottom:0.75rem;">{{ $message }}</p>
                @else
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 1rem;">
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
    <div class="admin-flash admin-flash--warning" style="margin-top:1rem;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Guarda la clave secreta en un lugar seguro. Sin ella no podrás recuperar el acceso si pierdes tu dispositivo.
    </div>

@endif

@endsection
