<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Admin — NovaReef</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons" defer></script>
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
</head>
<body class="admin-body">

<div class="admin-login">

    {{-- ═══ PANEL IZQUIERDO — BRANDING ═══ --}}
    <div class="admin-login__brand">
        <div class="admin-login__brand-inner">

            {{-- Logo --}}
            <div class="admin-login__logo-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    <path d="M2 12h20"/>
                </svg>
            </div>

            <h1 class="admin-login__brand-name">NovaReef</h1>
            <p class="admin-login__tagline">
                Sistema de gestión para colegios de árbitros de fútbol en Colombia.
            </p>

            {{-- Features --}}
            <div class="admin-login__features">
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i data-feather="shield"></i>
                    </div>
                    <span>Control total de colegios y suscripciones</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i data-feather="users"></i>
                    </div>
                    <span>Gestión centralizada de usuarios y roles</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i data-feather="activity"></i>
                    </div>
                    <span>Auditoría de accesos y actividad del sistema</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i data-feather="lock"></i>
                    </div>
                    <span>Autenticación de dos factores (2FA)</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══ PANEL DERECHO — FORMULARIO ═══ --}}
    <div class="admin-login__panel">
        <div class="admin-login__form">

            <h2 class="admin-login__form-title">Bienvenido</h2>
            <p class="admin-login__form-sub">Ingresa tus credenciales de administrador</p>

            {{-- Error global --}}
            @if ($errors->any())
            <div class="a-alert a-alert--danger">
                <i data-feather="alert-circle"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="a-field">
                    <label for="email">Correo electrónico</label>
                    <div class="a-input-wrap">
                        <span class="a-icon"><i data-feather="mail"></i></span>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="admin@novareef.com"
                               autocomplete="email"
                               class="a-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               autofocus>
                    </div>
                    @error('email')
                        <p class="a-field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contraseña --}}
                <div class="a-field" style="margin-bottom:1.5rem;">
                    <label for="password">Contraseña</label>
                    <div class="a-input-wrap">
                        <span class="a-icon"><i data-feather="lock"></i></span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               class="a-input {{ $errors->has('password') ? 'is-invalid' : '' }}">
                    </div>
                    @error('password')
                        <p class="a-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="a-btn a-btn--primary a-btn--full">
                    <i data-feather="log-in"></i>
                    Ingresar al panel
                </button>

                <p style="text-align:center;margin-top:1rem;font-size:0.8125rem;color:var(--text-muted);">
                    <a href="#" style="color:var(--text);transition:color .2s;"
                       onmouseover="this.style.color='var(--text-bright)'"
                       onmouseout="this.style.color='var(--text)'">
                        ¿Olvidaste tu contraseña?
                    </a>
                </p>

            </form>

            <div class="a-security-note">
                <i data-feather="shield"></i>
                Acceso restringido — Solo administradores autorizados
            </div>

        </div>
    </div>

</div>

</body>
</html>
