@extends('layouts.app')

@section('titulo', 'Panel de control')

@php
    $dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto',
                'septiembre','octubre','noviembre','diciembre'];
    $hoy    = $dias[now()->dayOfWeek]
            . ', ' . now()->day
            . ' de ' . $meses[now()->month - 1]
            . ' de ' . now()->year;
@endphp

@section('contenido')
<div class="container">

    {{-- === BIENVENIDA === --}}
    <section class="welcome-card">
        <div class="welcome-badge">
            <span class="badge-dot"></span>
            Sesión activa
        </div>
        <h1 class="welcome-title">Bienvenido a NovaReef</h1>
        <p class="welcome-sub">
            Hola, <strong>{{ Auth::user()->nombreUsuario }}</strong>. Desde aquí gestionas
            las operaciones de tu colegio de árbitros. Selecciona un módulo para comenzar.
        </p>
        <div class="welcome-meta">
            <div class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                     style="width:14px;height:14px;">
                    <path fill-rule="evenodd"
                          d="M4 1.75a.75.75 0 0 1 1.5 0V3h5V1.75a.75.75 0 0 1 1.5 0V3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2V1.75ZM4.5 6a.5.5 0 0 0 0 1h7a.5.5 0 0 0 0-1h-7Z"
                          clip-rule="evenodd"/>
                </svg>
                {{ $hoy }}
            </div>
            <div class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                     style="width:14px;height:14px;">
                    <path fill-rule="evenodd"
                          d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM8 4a.75.75 0 0 1 .75.75v2.69l1.28 1.28a.75.75 0 0 1-1.06 1.06l-1.5-1.5A.75.75 0 0 1 7.25 7.75v-3A.75.75 0 0 1 8 4Z"
                          clip-rule="evenodd"/>
                </svg>
                {{ now()->format('H:i') }} (hora local)
            </div>
        </div>
    </section>

    {{-- === ESTADÍSTICAS === --}}
    <div class="section-head">
        <span class="section-label">Resumen del colegio</span>
        <div class="section-rule"></div>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon-box ic-emerald">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z"/>
                </svg>
            </div>
            <div class="stat-value val-emerald">{{ $arbitrosRegistrados }}</div>
            <div class="stat-label">Árbitros registrados</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-teal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path fill-rule="evenodd"
                          d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="stat-value val-teal">{{ $arbitrosActivos }}</div>
            <div class="stat-label">Árbitros activos</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-amber">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="stat-value val-amber">{{ $arbitrosProceso }}</div>
            <div class="stat-label">En proceso de ingreso</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-purple">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z"/>
                </svg>
            </div>
            <div class="stat-value val-purple">{{ $totalUsuarios }}</div>
            <div class="stat-label">Usuarios del colegio</div>
        </div>

    </div>

    {{-- === MÓDULOS === --}}
    <section class="modules-section">
        <div class="section-head">
            <span class="section-label">Módulos disponibles</span>
            <div class="section-rule"></div>
        </div>
        <div class="modules-grid">

            {{-- Árbitros --}}
            @can('ver-arbitros')
            <a href="{{ route('arbitros.index') }}" class="module-card module-card--link">
                <div class="mod-icon-box ic-teal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0
                                 A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Árbitros</div>
                    <div class="mod-desc">Expedientes, categorías y estadísticas</div>
                </div>
            </a>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-teal" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0
                                 A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Árbitros</div>
                    <div class="mod-desc" style="opacity:0.3;">Expedientes, categorías y estadísticas</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

            {{-- Torneos --}}
            @can('ver-torneos')
            <div class="module-card">
                <div class="mod-icon-box ic-amber">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125
                                 -1.125-1.125h-6.75A1.125 1.125 0 0 0 7.5 15.375v3.375m0 0H12m0-9V3m0 3.375c0 .621
                                 .504 1.125 1.125 1.125h3.75c.621 0 1.125-.504 1.125-1.125V3m-4.875 0H3m18 0h-5.25"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Torneos</div>
                    <div class="mod-desc">Competencias, equipos y partidos</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-amber" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125
                                 -1.125-1.125h-6.75A1.125 1.125 0 0 0 7.5 15.375v3.375m0 0H12m0-9V3m0 3.375c0 .621
                                 .504 1.125 1.125 1.125h3.75c.621 0 1.125-.504 1.125-1.125V3m-4.875 0H3m18 0h-5.25"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Torneos</div>
                    <div class="mod-desc" style="opacity:0.3;">Competencias, equipos y partidos</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

            {{-- Designaciones --}}
            @can('ver-designaciones')
            <div class="module-card featured">
                <div class="mod-icon-box ic-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1
                                 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18
                                 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Designaciones</div>
                    <div class="mod-desc">Asignación de árbitros a partidos</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-emerald" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1
                                 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18
                                 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Designaciones</div>
                    <div class="mod-desc" style="opacity:0.3;">Asignación de árbitros a partidos</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

            {{-- Finanzas --}}
            @can('ver-finanzas')
            <div class="module-card">
                <div class="mod-icon-box ic-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75
                                 M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25
                                 M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504
                                 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0
                                 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75
                                 A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Finanzas</div>
                    <div class="mod-desc">Pagos, cuotas e ingresos del colegio</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-blue" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75
                                 M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25
                                 M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504
                                 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0
                                 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75
                                 A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Finanzas</div>
                    <div class="mod-desc" style="opacity:0.3;">Pagos, cuotas e ingresos del colegio</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

            {{-- Académico --}}
            @can('ver-academico')
            <div class="module-card">
                <div class="mod-icon-box ic-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1
                                 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906
                                 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814
                                 m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-1.342"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Académico</div>
                    <div class="mod-desc">Cursos, evaluaciones y formación</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-purple" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1
                                 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906
                                 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814
                                 m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-1.342"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Académico</div>
                    <div class="mod-desc" style="opacity:0.3;">Cursos, evaluaciones y formación</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

            {{-- Sanciones --}}
            @can('ver-sanciones')
            <div class="module-card">
                <div class="mod-icon-box ic-red">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
                                 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Sanciones</div>
                    <div class="mod-desc">Gestión disciplinaria de árbitros</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>
            @else
            <div class="module-card module-card--locked">
                <div class="mod-icon-box ic-red" style="opacity:0.35;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
                                 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name" style="opacity:0.4;">Sanciones</div>
                    <div class="mod-desc" style="opacity:0.3;">Gestión disciplinaria de árbitros</div>
                </div>
                <span class="mod-badge badge-locked">Sin acceso</span>
            </div>
            @endcan

        </div>
    </section>

</div>
@endsection
