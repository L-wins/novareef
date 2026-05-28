<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Panel') — NovaReef Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons" defer></script>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    <path d="M2 12h20"/>
                </svg>
            </div>
            <span class="navbar__logo-text">NovaReef</span>
        </div>

        {{-- Menú --}}
        <ul class="navbar__menu">

            <li class="navbar__item">
                <a href="{{ route('admin.dashboard') }}"
                   class="navbar__link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i data-feather="home"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.colegios.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.colegios.*') ? 'active' : '' }}">
                    <i data-feather="shield"></i>
                    <span>Colegios</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.planes.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.planes.*') ? 'active' : '' }}">
                    <i data-feather="credit-card"></i>
                    <span>Planes</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.usuarios.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i data-feather="users"></i>
                    <span>Usuarios</span>
                </a>
            </li>

            <li class="navbar__item">
                <a href="{{ route('admin.logs.index') }}"
                   class="navbar__link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i data-feather="activity"></i>
                    <span>Logs</span>
                </a>
            </li>

            <li class="navbar__divider"></li>

            <li class="navbar__item">
                <a href="{{ route('admin.2fa.config') }}"
                   class="navbar__link {{ request()->routeIs('admin.2fa.config') ? 'active' : '' }}">
                    <i data-feather="settings"></i>
                    <span>Configuración</span>
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
                    <i data-feather="log-out"></i>
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
                @if(Auth::guard('admin')->user()->two_factor_enabled)
                    <span class="badge badge--2fa-on">
                        <i data-feather="lock" style="width:11px;height:11px;"></i>
                        2FA ✓
                    </span>
                @else
                    <a href="{{ route('admin.2fa.config') }}" class="badge badge--2fa-off">
                        <i data-feather="unlock" style="width:11px;height:11px;"></i>
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
                <i data-feather="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="admin-flash admin-flash--danger">
                <i data-feather="alert-circle"></i>
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
