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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         style="width:18px;height:18px;color:#fff;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        <path d="M2 12h20"/>
                    </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                             style="width:15px;height:15px;flex-shrink:0;">
                            <path fill-rule="evenodd"
                                  d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z"
                                  clip-rule="evenodd"/>
                            <path fill-rule="evenodd"
                                  d="M6 10a.75.75 0 0 1 .75-.75h9.546l-1.048-1.01a.75.75 0 1 1 1.044-1.078l2.5 2.5a.75.75 0 0 1 0 1.078l-2.5 2.5a.75.75 0 1 1-1.044-1.077l1.048-1.012H6.75A.75.75 0 0 1 6 10Z"
                                  clip-rule="evenodd"/>
                        </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0
                                 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1
                                 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                @can('ver-arbitros')
                <a href="{{ route('arbitros.index') }}"
                   class="sidebar-link {{ request()->routeIs('arbitros.*') && !request()->routeIs('categorias.arbitro.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                        <path fill-rule="evenodd"
                              d="M3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0
                                 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0
                                 0-13.074.003Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span>Árbitros</span>
                </a>
                @endcan

                @can('editar-arbitros')
                <a href="{{ route('categorias.arbitro.index') }}"
                   class="sidebar-link sidebar-link--sub {{ request()->routeIs('categorias.arbitro.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 10.5a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Z" clip-rule="evenodd"/>
                    </svg>
                    <span>Categorías</span>
                </a>
                @endcan

                @can('ver-torneos')
                <a href="{{ route('torneos.index') }}"
                   class="sidebar-link {{ request()->routeIs('torneos.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2
                                 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10
                                 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span>Torneos</span>
                </a>
                @endcan

                @can('ver-designaciones')
                <a href="{{ route('designaciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('designaciones.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M6 3.75A2.75 2.75 0 0 1 8.75 1h2.5A2.75 2.75 0 0 1 14 3.75v.443c.572.055
                                 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694
                                 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694
                                 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047
                                 0 0 1 6 4.193V3.75Zm6.5 0v.325a41.622 41.622 0 0 0-5 0V3.75c0-.69.56-1.25
                                 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25ZM10 10a1 1 0 0 0-1 1v.01a1 1 0 0 0
                                 2 0V11a1 1 0 0 0-1-1Z"
                              clip-rule="evenodd"/>
                        <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.1.644 4.313.991 6.61.991 2.297
                                 0 4.51-.347 6.61-.992.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363
                                 2.686a41.454 41.454 0 0 1-9.274 0C3.985 17.585 3 16.402 3 15.055Z"/>
                    </svg>
                    <span>Designaciones</span>
                </a>
                @endcan

                @can('ver-finanzas')
                <a href="{{ route('finanzas.index') }}"
                   class="sidebar-link {{ request()->routeIs('finanzas.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M1 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4Zm12
                                 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm13-1a1 1
                                 0 1 1-2 0 1 1 0 0 1 2 0Z"
                              clip-rule="evenodd"/>
                        <path d="M1 17a1 1 0 0 1 1-1h16a1 1 0 1 1 0 2H2a1 1 0 0 1-1-1Z"/>
                    </svg>
                    <span>Finanzas</span>
                </a>
                @endcan

                @can('ver-academico')
                <a href="{{ route('academico.index') }}"
                   class="sidebar-link {{ request()->routeIs('academico.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 16.82A7.462 7.462 0 0 1 15 15.5c.71 0 1.396.098 2.046.282A.75.75 0 0
                                 0 18 15.06v-11a.75.75 0 0 0-.546-.721A9.006 9.006 0 0 0 15 3a8.963 8.963 0
                                 0 0-4.25 1.065V16.82ZM9.25 4.065A8.963 8.963 0 0 0 5 3c-.85 0-1.673.118-2.454.339A.75.75
                                 0 0 0 2 4.06v11a.75.75 0 0 0 .954.721A7.506 7.506 0 0 1 5 15.5c1.579 0
                                 3.042.487 4.25 1.32V4.065Z"/>
                    </svg>
                    <span>Académico</span>
                </a>
                @endcan

                @can('ver-sanciones')
                <a href="{{ route('sanciones.index') }}"
                   class="sidebar-link {{ request()->routeIs('sanciones.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06
                                 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75
                                 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28
                                 7.22Z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span>Sanciones</span>
                </a>
                @endcan

                @if(Auth::user()->rolUsuario === 'superadmin')
                <div class="sidebar-divider"></div>
                <a href="{{ route('colegios.index') }}"
                   class="sidebar-link {{ request()->routeIs('colegios.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M1 2.75A.75.75 0 0 1 1.75 2h10.5a.75.75 0 0 1 0 1.5H12v13.75a.75.75
                                 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75v-2.5a.75.75 0 0 0-.75-.75h-2.5a.75.75
                                 0 0 0-.75.75v2.5a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V2.75ZM4
                                 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0
                                 1-.5-.5v-1ZM4.5 9a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0
                                 .5-.5v-1a.5.5 0 0 0-.5-.5h-1ZM8 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5
                                 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM8.5 9a.5.5 0 0 0-.5.5v1a.5.5 0 0 0
                                 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Z"
                              clip-rule="evenodd"/>
                        <path d="M10.75 14.25a.75.75 0 0 1 .75-.75H16a.75.75 0 0 1 .75.75v3.25H19a.75.75
                                 0 0 1 0 1.5H1a.75.75 0 0 1 0-1.5h2.25V4.75a.75.75 0 0 1 .75-.75H7a.75.75
                                 0 0 1 .75.75V11h2.25a.75.75 0 0 1 .75.75v2.5Z"/>
                    </svg>
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
