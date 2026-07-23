<!DOCTYPE html>
<html lang="es" data-theme-pref="{{ Auth::user()->temaPreferencia ?? 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'Panel de control — NovaReef')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <title>@yield('titulo', 'Panel') — NovaReef</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-light.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-dark.png') }}" media="(prefers-color-scheme: dark)">
    @include('layouts.partials.theme-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body @if(session('impersonacion.idAdmin')) style="--banner-h: 40px;" @endif>

    @if(session('impersonacion.idAdmin'))
    <div class="impersonacion-banner">
        <i class="fa-solid fa-user-secret"></i>
        <span>Estás viendo NovaReef como <strong>{{ Auth::user()->nombreUsuario }}</strong> — sesión de soporte.</span>
        <form method="POST" action="{{ route('impersonacion.salir') }}">
            @csrf
            <button type="submit" class="impersonacion-banner__salir">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </form>
    </div>
    @endif

    {{-- ===== NAVBAR ===== --}}
    <header class="navbar" id="navbar">
        <div class="nav-inner">

            {{-- Marca: logo y nombre del colegio si existen; NovaReef como fallback --}}
            @php($colegioNav = Auth::user()->colegio)
            <div class="nav-brand">
                <div class="brand-icon brand-icon--logo">
                    @if ($colegioNav?->logoUrl)
                        <img src="{{ $colegioNav->logoUrl }}" alt="Logo de {{ $colegioNav->nombreColegio }}">
                    @else
                        <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef" class="brand-icon__novareef brand-icon__novareef--dark">
                        <img src="{{ asset('images/logo/novareef-logo-icontile-light.png') }}" alt="NovaReef" class="brand-icon__novareef brand-icon__novareef--light">
                    @endif
                </div>
                <span class="brand-name" title="{{ $colegioNav?->nombreColegio ?? 'NovaReef' }}">
                    {{ $colegioNav?->nombreColegio ?? 'NovaReef' }}
                </span>
                <span class="brand-sep"></span>
                <span class="brand-section">@yield('seccion', 'Panel de control')</span>
            </div>

            {{-- Reloj en vivo — columna central propia (ver .nav-inner en grid),
                 fijo a America/Bogota. La app corre en UTC (config/app.php),
                 así que hay que convertir explícitamente, no usar now() tal
                 cual. Valor inicial en servidor (sin flash de vacío) + epoch
                 para que reloj.js corrija cualquier reloj de equipo
                 desincronizado. --}}
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
                     data-theme-endpoint="{{ route('preferencias.tema') }}">
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
                <a href="{{ route('disponibilidad.index') }}"
                   class="sidebar-link {{ request()->routeIs('disponibilidad.index') || request()->routeIs('disponibilidad.store') || request()->routeIs('disponibilidad.extraordinaria') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Mi disponibilidad</span>
                </a>
                <a href="{{ route('mis-partidos.index') }}"
                   class="sidebar-link {{ request()->routeIs('mis-partidos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-futbol"></i>
                    <span>Mis partidos</span>
                </a>
                <a href="{{ route('arbitros.estado-cuenta') }}"
                   class="sidebar-link {{ request()->routeIs('arbitros.estado-cuenta*') ? 'active' : '' }}">
                    <i class="fa-solid fa-sack-dollar"></i>
                    <span>Mi estado de cuenta</span>
                </a>
                @endif

                @can('crear-designaciones')
                <a href="{{ route('disponibilidad.general') }}"
                   class="sidebar-link {{ request()->routeIs('disponibilidad.general') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Disponibilidad</span>
                </a>
                @endcan

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
                <a href="{{ route('requisitos-documentos-arbitro.index') }}"
                   class="sidebar-link sidebar-link--sub {{ request()->routeIs('requisitos-documentos-arbitro.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-folder-tree"></i>
                    <span>Documentos</span>
                </a>
                @endcan

                @if (in_array('torneos', $modulosPlan ?? [], true))
                @can('ver-torneos')
                <a href="{{ route('torneos.index') }}"
                   class="sidebar-link {{ request()->routeIs('torneos.*') || request()->routeIs('partidos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-trophy"></i>
                    <span>Torneos</span>
                </a>
                @endcan
                @endif

                @if (in_array('designaciones', $modulosPlan ?? [], true))
                @can('ver-designaciones')
                <a href="{{ route('designaciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('designaciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Designaciones</span>
                </a>
                @endcan
                @endif

                @if (in_array('finanzas', $modulosPlan ?? [], true))
                @can('ver-finanzas')
                <a href="{{ route('finanzas.balance.index') }}"
                   class="sidebar-link {{ request()->routeIs('finanzas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Finanzas</span>
                </a>
                @endcan
                @endif

                @if (in_array('academico', $modulosPlan ?? [], true))
                @can('crear-academico')
                <a href="{{ route('academico.sesiones.index') }}"
                   class="sidebar-link {{ request()->routeIs('academico.sesiones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Académico</span>
                </a>
                @else
                @can('ver-academico')
                <a href="{{ route('academico.mis-clases') }}"
                   class="sidebar-link {{ request()->routeIs('academico.mis-clases') || request()->routeIs('academico.justificaciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Mis Clases</span>
                </a>
                @endcan
                @endcan
                @endif

                @if (in_array('sanciones', $modulosPlan ?? [], true))
                @can('ver-sanciones')
                <a href="{{ route('sanciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('sanciones.*') && ! request()->routeIs('sanciones.justificaciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gavel"></i>
                    <span>Sanciones</span>
                </a>
                @endcan
                @endif

                {{-- Independiente de ver-sanciones a propósito: quien revisa
                     justificaciones académicas es instructor/ejecutivo/sanciones
                     (permiso editar-academico), y el rol tecnico no tiene
                     ver-sanciones — igual debe ver este acceso. --}}
                @if (in_array('academico', $modulosPlan ?? [], true))
                @can('editar-academico')
                <a href="{{ route('sanciones.justificaciones.pendientes') }}"
                   class="sidebar-link sidebar-link--sub {{ request()->routeIs('sanciones.justificaciones.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-circle-question"></i>
                    <span>Justificaciones</span>
                </a>
                @endcan
                @endif

                @can('editar-arbitros')
                <div class="sidebar-divider"></div>
                <a href="{{ route('configuracion.index') }}"
                   class="sidebar-link {{ request()->routeIs('configuracion.index', 'configuracion.colegio') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuración</span>
                </a>
                @endcan

                @can('gestionar-cuentas-admin')
                <a href="{{ route('configuracion.cuentas-admin.index') }}"
                   class="sidebar-link sidebar-link--sub {{ request()->routeIs('configuracion.cuentas-admin.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Cuentas Admin</span>
                </a>
                @endcan

            </nav>
        </aside>

        {{-- ===== CONTENIDO PRINCIPAL ===== --}}
        <main class="main">
            @yield('contenido')
        </main>

    </div>

    @stack('scripts')

    {{-- ===== Flash messages vía SweetAlert2 ===== --}}
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
