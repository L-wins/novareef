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
            Hola, <strong>{{ Auth::user()->name }}</strong>. Desde aquí gestionas
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
            <div class="stat-value val-emerald">—</div>
            <div class="stat-label">Árbitros registrados</div>
            <div class="stat-note">Disponible próximamente</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-amber">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path fill-rule="evenodd"
                          d="M10 1c-1.828 0-3.623.149-5.371.435a.75.75 0 0 0-.629.74v.387c-.827.157-1.5.49-1.5 1.121v1.566c0 .945.564 1.721 1.5 1.943V14a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1V7.192c.936-.222 1.5-.998 1.5-1.943V3.682c0-.631-.673-.964-1.5-1.121V2.175a.75.75 0 0 0-.629-.74A33.227 33.227 0 0 0 10 1Z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="stat-value val-amber">—</div>
            <div class="stat-label">Torneos activos</div>
            <div class="stat-note">Disponible próximamente</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-blue">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path fill-rule="evenodd"
                          d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="stat-value val-blue">—</div>
            <div class="stat-label">Designaciones</div>
            <div class="stat-note">Disponible próximamente</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box ic-purple">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     style="width:20px;height:20px;">
                    <path fill-rule="evenodd"
                          d="M1 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4Zm12 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm13-1a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM1.75 14.5a.75.75 0 0 0 0 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 0 0-1.5 0v.784a.272.272 0 0 1-.35.25A49.043 49.043 0 0 0 1.75 14.5Z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="stat-value val-purple">—</div>
            <div class="stat-label">Ingresos del mes</div>
            <div class="stat-note">Disponible próximamente</div>
        </div>

    </div>

    {{-- === MÓDULOS === --}}
    <section class="modules-section">
        <div class="section-head">
            <span class="section-label">Módulos disponibles</span>
            <div class="section-rule"></div>
        </div>
        <div class="modules-grid">

            {{-- M01 Colegios --}}
            <div class="module-card">
                <div class="mod-icon-box ic-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" style="width:22px;height:22px;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5
                                 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125
                                 h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>
                <div class="mod-info">
                    <div class="mod-name">Gestión de Colegios</div>
                    <div class="mod-desc">Información institucional y configuración</div>
                </div>
                <span class="mod-badge badge-soon">Pronto</span>
            </div>

            {{-- M02 Árbitros --}}
            <div class="module-card">
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
                <span class="mod-badge badge-soon">Pronto</span>
            </div>

            {{-- M03 Torneos --}}
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

            {{-- M04 Designaciones (módulo clave) --}}
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
                <span class="mod-badge badge-key">Clave</span>
            </div>

            {{-- M05 Académico --}}
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

            {{-- M06 Finanzas --}}
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

        </div>
    </section>

</div>
@endsection
