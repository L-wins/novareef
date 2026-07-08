<!DOCTYPE html>
<html lang="es" data-theme-pref="{{ Auth::guard('admin')->user()->temaPreferencia ?? 'dark' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <title>@yield('titulo', 'Panel') — NovaReef Admin</title>
    @include('layouts.partials.theme-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
    @stack('styles')
</head>
<body class="admin-body">

<div class="admin-wrapper">

    {{-- ═══════ SIDEBAR ═══════ --}}
    <nav class="navbar">

        {{-- Logo --}}
        <div class="navbar__logo">
            <div class="navbar__logo-icon">
                <i class="fa-solid fa-futbol"></i>
            </div>
            <span class="navbar__logo-text">NovaReef</span>
        </div>

        {{-- Menú --}}
        <ul class="navbar__menu">

            <li class="navbar__item">
                <a href="{{ route('admin.dashboard') }}"
                   class="navbar__link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.colegios.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.colegios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Colegios</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.planes.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.planes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Planes</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.suscripciones.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.suscripciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Suscripciones</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.usuarios.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.logs.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Logs</span>
                </a>
            </li>

            <li class="navbar__divider"></li>

            <li class="navbar__item">
                <a href="{{ route('admin.2fa.config') }}"
                   class="navbar__link {{ request()->routeIs('admin.2fa.config') ? 'active' : '' }}">
                    <i class="fa-solid fa-key"></i>
                    <span>Seguridad</span>
                </a>
            </li>

        </ul>

        {{-- Footer: usuario + cerrar sesión --}}
        <div class="navbar__footer">
            <div class="navbar__user">
                <div class="navbar__avatar">
                    {{ strtoupper(substr(Auth::guard('admin')->user()->nombre, 0, 2)) }}
                </div>
                <span class="navbar__username">
                    {{ Auth::guard('admin')->user()->nombre }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="navbar__logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>

    </nav>

    {{-- ═══════ CONTENIDO ═══════ --}}
    <div class="admin-content">

        {{-- Header superior --}}
        <header class="admin-header">
            <h1 class="admin-header__title">@yield('titulo', 'Panel')</h1>

            <div class="admin-header__right">
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

                @if(Auth::guard('admin')->user()->two_factor_enabled)
                    <span class="badge badge--2fa-on">
                        <i class="fa-solid fa-lock"></i>
                        2FA ✓
                    </span>
                @else
                    <a href="{{ route('admin.2fa.config') }}" class="badge badge--2fa-off">
                        <i class="fa-solid fa-lock-open"></i>
                        2FA ✗
                    </a>
                @endif
            </div>
        </header>

        {{-- Main --}}
        <main class="admin-main">

            {{-- Flash messages --}}
            @if(session('success'))
            <div class="admin-flash admin-flash--success">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="admin-flash admin-flash--danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ session('error') }}
            </div>
            @endif

            @yield('contenido')

        </main>
    </div>

</div>

@stack('scripts')
</body>
</html>
