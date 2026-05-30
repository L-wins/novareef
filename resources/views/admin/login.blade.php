<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Admin — NovaReef</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
</head>
<body class="admin-body">

<div class="admin-login">

    {{-- ═══ PANEL IZQUIERDO — BRANDING ═══ --}}
    <div class="admin-login__brand">
        <div class="admin-login__brand-inner">

            {{-- Logo --}}
            <div class="admin-login__logo-wrap">
                <i class="fa-solid fa-futbol"></i>
            </div>

            <h1 class="admin-login__brand-name">NovaReef</h1>
            <p class="admin-login__tagline">
                Panel de control del ecosistema de colegios de árbitros.
                Acceso restringido a administradores autorizados.
            </p>

            {{-- Features --}}
            <div class="admin-login__features">
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <span>Control total de colegios y suscripciones</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span>Gestión centralizada de usuarios y roles</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <span>Auditoría de accesos y actividad del sistema</span>
                </div>
                <div class="admin-login__feature">
                    <div class="admin-login__feature-ic">
                        <i class="fa-solid fa-shield-halved"></i>
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
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="a-field">
                    <label for="email">Correo electrónico</label>
                    <div class="a-input-wrap">
                        <span class="a-icon"><i class="fa-solid fa-envelope"></i></span>
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
                        <span class="a-icon"><i class="fa-solid fa-lock"></i></span>
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
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Ingresar al panel
                </button>

            </form>

            <div class="a-security-note">
                <i class="fa-solid fa-shield-halved"></i>
                Acceso restringido — Solo administradores autorizados
            </div>

            {{-- Volver al sitio público --}}
            <p style="text-align:center;margin-top:1.5rem;font-size:0.8125rem;">
                <a href="{{ route('welcome') }}"
                   style="color:var(--text-muted);text-decoration:none;transition:color .2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.color='var(--text-bright)'"
                   onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i>
                    Volver al sitio público
                </a>
            </p>

        </div>
    </div>

</div>

</body>
</html>
