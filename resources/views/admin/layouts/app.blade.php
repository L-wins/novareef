<!DOCTYPE html>
<html lang="es" data-theme-pref="{{ Auth::guard('admin')->user()->temaPreferencia ?? 'dark' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <title>@yield('titulo', 'Panel') — NovaReef Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-light.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-dark.png') }}" media="(prefers-color-scheme: dark)">
    @include('layouts.partials.theme-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
    @stack('styles')
</head>
<body class="admin-body">

    {{-- ===== NAVBAR ===== --}}
    <header class="navbar" id="navbar">
        <div class="nav-inner">

            {{-- Marca: el superadmin no pertenece a un colegio — siempre NovaReef --}}
            <div class="nav-brand">
                <div class="brand-icon brand-icon--logo">
                    <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef" class="brand-icon__novareef brand-icon__novareef--dark">
                    <img src="{{ asset('images/logo/novareef-logo-icontile-light.png') }}" alt="NovaReef" class="brand-icon__novareef brand-icon__novareef--light">
                </div>
                <span class="brand-name">NovaReef</span>
                <span class="brand-sep"></span>
                <span class="brand-section">@yield('titulo', 'Panel SuperAdmin')</span>
            </div>

            {{-- Reloj en vivo — mismo componente y misma corrección de huso
                 horario/offset que el panel usuario (shared/reloj.js). --}}
            @php($ahoraBogota = now()->setTimezone('America/Bogota'))
            <div id="nav-reloj" class="nav-reloj" aria-live="off"
                 data-server-epoch="{{ now()->getPreciseTimestamp(3) }}">
                <i class="fa-regular fa-clock nav-reloj__icon"></i>
                <span class="nav-reloj__fecha">{{ ucfirst($ahoraBogota->locale('es')->isoFormat('ddd DD MMM')) }}</span>
                <span class="nav-reloj__hora">{{ $ahoraBogota->format('h:i:s A') }}</span>
            </div>

            {{-- Acciones --}}
            <div class="nav-actions">

                {{-- Selector de tema --}}
                <div class="theme-switch" role="radiogroup" aria-label="Tema de la interfaz"
                     data-theme-endpoint="{{ route('admin.preferencias.tema') }}">
                    <button type="button" class="theme-switch__btn" data-theme-set="light"
                            title="Tema claro" aria-label="Tema claro">
                        <i class="fa-solid fa-sun"></i>
                    </button>
                    <button type="button" class="theme-switch__btn" data-theme-set="dark"
                            title="Tema oscuro" aria-label="Tema oscuro">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <button type="button" class="theme-switch__btn" data-theme-set="system"
                            title="Según el sistema" aria-label="Tema según el sistema">
                        <i class="fa-solid fa-desktop"></i>
                    </button>
                </div>

                {{-- Insignia 2FA — propia del guard admin --}}
                @if(Auth::guard('admin')->user()->two_factor_enabled)
                    <span class="badge badge--2fa-on">
                        <i class="fa-solid fa-lock"></i>
                        <span>2FA ✓</span>
                    </span>
                @else
                    <a href="{{ route('admin.2fa.config') }}" class="badge badge--2fa-off">
                        <i class="fa-solid fa-lock-open"></i>
                        <span>2FA ✗</span>
                    </a>
                @endif

                {{-- Chip de usuario --}}
                <div class="user-chip">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::guard('admin')->user()->nombre, 0, 2)) }}</div>
                    <span class="user-name">{{ Auth::guard('admin')->user()->nombre }}</span>
                </div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout" aria-label="Cerrar sesión">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="btn-logout-text">Cerrar sesión</span>
                    </button>
                </form>

            </div>
        </div>
    </header>

    {{-- ===== SIDEBAR DE NAVEGACIÓN ===== --}}
    <div class="app-layout">

        <aside class="sidebar" aria-label="Navegación principal">
            <nav class="sidebar-nav">

                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.colegios.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.colegios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Colegios</span>
                </a>

                <a href="{{ route('admin.planes.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.planes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Planes</span>
                </a>

                <a href="{{ route('admin.suscripciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.suscripciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Suscripciones</span>
                </a>

                <a href="{{ route('admin.usuarios.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Usuarios</span>
                </a>

                <a href="{{ route('admin.logs.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Logs</span>
                </a>

                <div class="sidebar-divider"></div>

                <a href="{{ route('admin.2fa.config') }}"
                   class="sidebar-link {{ request()->routeIs('admin.2fa.config') ? 'active' : '' }}">
                    <i class="fa-solid fa-key"></i>
                    <span>Seguridad</span>
                </a>

            </nav>
        </aside>

        {{-- ===== CONTENIDO PRINCIPAL ===== --}}
        <main class="main admin-main">
            @yield('contenido')
        </main>

    </div>

@stack('scripts')

{{-- Flash messages vía SweetAlert2 — mismo toast que el panel usuario --}}
@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.novaAlert) novaAlert.success(@json(session('success')));
    });
</script>
@endif

@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.novaAlert) novaAlert.error(@json(session('error')));
    });
</script>
@endif
</body>
</html>
