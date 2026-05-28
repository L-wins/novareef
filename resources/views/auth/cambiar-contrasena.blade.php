@extends('layouts.auth')

@section('titulo', 'Cambiar contraseña')

@section('contenido')

<div class="login-card">

    {{-- Logo --}}
    <div class="login-logo">
        <div class="login-logo-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                <path d="M2 12h20"/>
            </svg>
        </div>
        <span class="login-logo-name">NovaReef</span>
    </div>

    {{-- Alerta de seguridad --}}
    <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;
                background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);
                border-radius:10px;margin-bottom:24px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             style="width:16px;height:16px;color:#f59e0b;flex-shrink:0;margin-top:1px;">
            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
        </svg>
        <p style="font-size:0.82rem;color:#f59e0b;line-height:1.5;margin:0;">
            Por seguridad debes cambiar tu contraseña antes de continuar.
        </p>
    </div>

    <h1 class="login-title">Crear nueva contraseña</h1>
    <p class="login-subtitle">Elige una contraseña segura de al menos 8 caracteres.</p>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="login-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="login-error-icon">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd"/>
            </svg>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}" class="login-form">
        @csrf

        {{-- Nueva contraseña --}}
        <div class="login-field">
            <label for="nueva_password" class="login-label">Nueva contraseña</label>
            <div class="login-input-wrap">
                <span class="login-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <input type="password"
                       id="nueva_password"
                       name="nueva_password"
                       autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres"
                       class="login-input {{ $errors->has('nueva_password') ? 'is-invalid' : '' }}">
                <button type="button" class="toggle-pwd" data-target="nueva_password" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41Z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Confirmar contraseña --}}
        <div class="login-field">
            <label for="nueva_password_confirmation" class="login-label">Confirmar contraseña</label>
            <div class="login-input-wrap">
                <span class="login-input-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <input type="password"
                       id="nueva_password_confirmation"
                       name="nueva_password_confirmation"
                       autocomplete="new-password"
                       placeholder="Repite la contraseña"
                       class="login-input {{ $errors->has('nueva_password_confirmation') ? 'is-invalid' : '' }}">
                <button type="button" class="toggle-pwd" data-target="nueva_password_confirmation" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41Z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <p id="pwd-match-msg" style="display:none;font-size:0.75rem;color:#f87171;margin-top:4px;">
                Las contraseñas no coinciden.
            </p>
        </div>

        <button type="submit" class="login-btn">
            Guardar contraseña
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
            </svg>
        </button>

    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;text-align:center;">
        @csrf
        <button type="submit"
                style="background:none;border:none;color:#475569;font-size:0.8rem;cursor:pointer;
                       text-decoration:underline;padding:0;">
            Cerrar sesión
        </button>
    </form>

</div>

@endsection
