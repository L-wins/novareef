<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'Panel de control — NovaReef')">
    <title>@yield('titulo', 'Panel') — NovaReef</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>

    {{-- ===== NAVBAR ===== --}}
    <header class="navbar" id="navbar">
        <div class="nav-inner">

            {{-- Marca --}}
            <div class="nav-brand">
                <div class="brand-icon">
                    <i class="fa-solid fa-futbol"></i>
                </div>
                <span class="brand-name">NovaReef</span>
                <span class="brand-sep"></span>
                <span class="brand-section">@yield('seccion', 'Panel de control')</span>
            </div>

            {{-- Acciones --}}
            <div class="nav-actions">

                {{-- Chip de usuario --}}
                <div class="user-chip">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->nombreUsuario, 0, 1)) }}</div>
                    <span class="user-name">{{ Auth::user()->nombreUsuario }}</span>
                </div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
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

                <a href="{{ route('dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>

                @if (Auth::user()->rolUsuario === 'arbitro')
                <a href="{{ route('arbitros.mi-perfil') }}"
                   class="sidebar-link {{ request()->routeIs('arbitros.mi-perfil*') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>Mi perfil</span>
                </a>
                @endif

                @can('ver-arbitros')
                <a href="{{ route('arbitros.index') }}"
                   class="sidebar-link {{ request()->routeIs('arbitros.index') || (request()->routeIs('arbitros.*') && !request()->routeIs('arbitros.mi-perfil*') && !request()->routeIs('categorias.arbitro.*')) ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Árbitros</span>
                </a>
                @endcan

                @can('editar-arbitros')
                <a href="{{ route('categorias.arbitro.index') }}"
                   class="sidebar-link sidebar-link--sub {{ request()->routeIs('categorias.arbitro.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i>
                    <span>Categorías</span>
                </a>
                @endcan

                @can('ver-torneos')
                <a href="{{ route('torneos.index') }}"
                   class="sidebar-link {{ request()->routeIs('torneos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-trophy"></i>
                    <span>Torneos</span>
                </a>
                @endcan

                @can('ver-designaciones')
                <a href="{{ route('designaciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('designaciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Designaciones</span>
                </a>
                @endcan

                @can('ver-finanzas')
                <a href="{{ route('finanzas.index') }}"
                   class="sidebar-link {{ request()->routeIs('finanzas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Finanzas</span>
                </a>
                @endcan

                @can('ver-academico')
                <a href="{{ route('academico.index') }}"
                   class="sidebar-link {{ request()->routeIs('academico.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Académico</span>
                </a>
                @endcan

                @can('ver-sanciones')
                <a href="{{ route('sanciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('sanciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gavel"></i>
                    <span>Sanciones</span>
                </a>
                @endcan

                @if(Auth::user()->rolUsuario === 'superadmin')
                <div class="sidebar-divider"></div>
                <a href="{{ route('colegios.index') }}"
                   class="sidebar-link {{ request()->routeIs('colegios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-building-columns"></i>
                    <span>Colegios</span>
                </a>
                @endif

            </nav>
        </aside>

        {{-- ===== CONTENIDO PRINCIPAL ===== --}}
        <main class="main">
            @yield('contenido')
        </main>

    </div>

    @stack('scripts')
</body>
</html>
