@extends('layouts.public')

@section('contenido')

    {{-- ===== NAVBAR ===== --}}
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-slate-950/80 backdrop-blur-lg transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            <path d="M2 12h20"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
                </div>

                {{-- Links de navegación (escritorio) --}}
                <nav class="hidden md:flex items-center gap-6">
                    <a href="#que-es"        class="text-sm text-slate-400 hover:text-white transition-colors">¿Qué es?</a>
                    <a href="#caracteristicas" class="text-sm text-slate-400 hover:text-white transition-colors">Características</a>
                    <a href="#para-quien"    class="text-sm text-slate-400 hover:text-white transition-colors">¿Para quién?</a>
                </nav>

                {{-- CTA + Admin + hamburger --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400
                              text-white font-semibold rounded-lg text-sm transition-colors duration-200
                              shadow-md shadow-emerald-500/20">
                        Iniciar sesión
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                        </svg>
                    </a>

                    {{-- Acceso admin (discreto) --}}
                    <a href="{{ route('admin.login') }}"
                       title="Acceso administrador"
                       class="flex items-center justify-center w-9 h-9 rounded-lg
                              bg-white/5 hover:bg-white/10 text-slate-500 hover:text-slate-300
                              border border-white/5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/>
                        </svg>
                    </a>

                    {{-- Botón hamburger (móvil) --}}
                    <button id="menu-toggle" aria-expanded="false" aria-label="Abrir menú"
                            class="md:hidden flex items-center justify-center w-9 h-9 rounded-lg
                                   bg-white/5 hover:bg-white/10 text-slate-300 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        {{-- Menú móvil desplegable --}}
        <div id="mobile-menu" class="md:hidden border-t border-white/5 bg-slate-950/95 backdrop-blur-lg">
            <nav class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-1">
                <a href="#que-es"
                   class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    ¿Qué es NovaReef?
                </a>
                <a href="#caracteristicas"
                   class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    Características
                </a>
                <a href="#para-quien"
                   class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    ¿Para quién es?
                </a>
            </nav>
        </div>
    </header>

    {{-- ===== HERO ===== --}}
    <section class="relative min-h-screen flex items-center pt-16 hero-bg grid-lines overflow-hidden">

        {{-- Círculos decorativos (centro de campo) --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 rounded-full border border-emerald-500/10"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[480px] h-[480px] rounded-full border border-emerald-500/[0.06]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[720px] h-[720px] rounded-full border border-emerald-500/[0.04]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-emerald-400/40"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-28 text-center">

            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10
                        border border-emerald-500/25 text-emerald-400 text-sm font-medium mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Plataforma SaaS para colegios de árbitros · Colombia
            </div>

            {{-- Título --}}
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight leading-none mb-6">
                <span class="text-white">Gestión moderna</span><br>
                <span class="glow-text">para árbitros de élite</span>
            </h1>

            {{-- Subtítulo --}}
            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-slate-400 leading-relaxed mb-10">
                NovaReef centraliza la operación completa de tu colegio de árbitros: designaciones,
                torneos, finanzas, formación y más, todo en una sola plataforma accesible desde
                cualquier dispositivo.
            </p>

            {{-- Botones --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-500
                          hover:bg-emerald-400 text-white font-bold rounded-xl text-base
                          transition-colors duration-200 shadow-xl shadow-emerald-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                    </svg>
                    Iniciar sesión
                </a>
                <a href="#que-es"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/5
                          hover:bg-white/10 text-white font-semibold rounded-xl text-base
                          transition-colors duration-200 border border-white/10">
                    Conocer más
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>

            {{-- Estadísticas rápidas --}}
            <div class="mt-20 grid grid-cols-2 sm:grid-cols-4 gap-8 max-w-3xl mx-auto">
                <div class="text-center">
                    <p class="text-3xl font-bold text-emerald-400">8</p>
                    <p class="text-sm text-slate-500 mt-1">Módulos integrados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-emerald-400">Multi</p>
                    <p class="text-sm text-slate-500 mt-1">Tenant por subdominio</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-emerald-400">5</p>
                    <p class="text-sm text-slate-500 mt-1">Roles por colegio</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-emerald-400">100%</p>
                    <p class="text-sm text-slate-500 mt-1">En la nube</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== ¿QUÉ ES NOVAREEF? ===== --}}
    <section id="que-es" class="py-24 bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                {{-- Texto --}}
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10
                                border border-emerald-500/20 text-emerald-400 text-sm font-medium mb-6">
                        ¿Qué es NovaReef?
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-6 leading-tight">
                        El sistema operativo de tu colegio de árbitros
                    </h2>
                    <p class="text-slate-400 text-lg leading-relaxed mb-5">
                        NovaReef es una plataforma SaaS multi-tenant diseñada para digitalizar y optimizar la
                        gestión administrativa, deportiva y financiera de los colegios de árbitros de fútbol
                        en Colombia.
                    </p>
                    <p class="text-slate-400 text-lg leading-relaxed mb-8">
                        Desde las designaciones de partidos hasta la formación académica de los árbitros,
                        todo en una sola herramienta accesible desde cualquier dispositivo.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-slate-300">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Subdominio exclusivo para cada colegio afiliado
                        </li>
                        <li class="flex items-center gap-3 text-slate-300">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Roles y permisos adaptados a cada función del colegio
                        </li>
                        <li class="flex items-center gap-3 text-slate-300">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Datos aislados y seguros por organización
                        </li>
                        <li class="flex items-center gap-3 text-slate-300">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Actualizaciones continuas sin interrumpir la operación
                        </li>
                    </ul>
                </div>

                {{-- Panel decorativo --}}
                <div class="relative">
                    <div class="bg-slate-800 rounded-2xl p-7 border border-white/5 shadow-2xl">
                        {{-- Barra de ventana --}}
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-3 h-3 rounded-full bg-red-500/70"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/70"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/70"></div>
                            <span class="ml-3 text-xs text-slate-500 font-mono">colegio.novareef.com · Panel principal</span>
                        </div>
                        {{-- Mock datos --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-700/50 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-slate-500 mb-1">Árbitros activos</p>
                                    <p class="text-2xl font-bold text-emerald-400">48</p>
                                </div>
                                <div class="bg-slate-700/50 rounded-xl p-4 border border-white/5">
                                    <p class="text-xs text-slate-500 mb-1">Partidos esta semana</p>
                                    <p class="text-2xl font-bold text-white">12</p>
                                </div>
                            </div>
                            <div class="bg-slate-700/50 rounded-xl p-4 border border-white/5">
                                <p class="text-xs text-slate-500 mb-3">Próximas designaciones</p>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-emerald-500/20 flex items-center justify-center text-xs font-bold text-emerald-400">JL</div>
                                            <span class="text-sm text-slate-300">Jorge López</span>
                                        </div>
                                        <span class="text-xs text-slate-500 bg-slate-600/40 px-2 py-0.5 rounded-full">Sáb 10:00</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-teal-500/20 flex items-center justify-center text-xs font-bold text-teal-400">AM</div>
                                            <span class="text-sm text-slate-300">Ana Martínez</span>
                                        </div>
                                        <span class="text-xs text-slate-500 bg-slate-600/40 px-2 py-0.5 rounded-full">Dom 15:30</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-blue-500/20 flex items-center justify-center text-xs font-bold text-blue-400">CR</div>
                                            <span class="text-sm text-slate-300">Carlos Ruiz</span>
                                        </div>
                                        <span class="text-xs text-slate-500 bg-slate-600/40 px-2 py-0.5 rounded-full">Dom 17:00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-emerald-500/10 rounded-xl p-3.5 border border-emerald-500/20 flex items-center gap-3">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-emerald-400 font-medium">3 pagos pendientes por procesar</p>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -inset-6 bg-emerald-500/5 rounded-3xl -z-10 blur-2xl"></div>
                </div>

            </div>
        </div>
    </section>

    {{-- ===== CARACTERÍSTICAS ===== --}}
    <section id="caracteristicas" class="py-24 bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10
                            border border-emerald-500/20 text-emerald-400 text-sm font-medium mb-4">
                    Módulos principales
                </div>
                <h2 class="text-4xl font-bold text-white mb-4">Todo lo que necesita tu colegio</h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    Ocho módulos integrados que cubren cada aspecto de la operación de un colegio
                    de árbitros profesional.
                </p>
            </div>

            {{-- Grid de módulos --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- M01 Colegios --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5
                                     m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75
                                     c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Gestión de Colegios</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Configura y administra la información institucional, accesos y parámetros
                        de cada colegio de árbitros afiliado a la plataforma.
                    </p>
                </div>

                {{-- M02 Árbitros --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0
                                     A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Registro de Árbitros</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Expedientes completos: datos personales, categorías, documentos,
                        historial de sanciones y estadísticas de participación en torneos.
                    </p>
                </div>

                {{-- M03 Torneos --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125
                                     h-6.75A1.125 1.125 0 0 0 7.5 15.375v3.375m0 0H12m0-9V3m0 3.375c0 .621.504 1.125 1.125 1.125
                                     h3.75c.621 0 1.125-.504 1.125-1.125V3m-4.875 0H3m18 0h-5.25"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Torneos y Competencias</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Organiza torneos, categorías, equipos y partidos. Control completo
                        del calendario competitivo de toda la temporada.
                    </p>
                </div>

                {{-- M04 Designaciones (módulo clave) --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-emerald-500/25 card-lift relative overflow-hidden">
                    <div class="absolute top-4 right-4">
                        <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-xs
                                     font-semibold rounded-full border border-emerald-500/25">
                            Módulo clave
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5
                                     v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1
                                     5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0
                                     2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5
                                     15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25
                                     h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25
                                     h.008v.008H16.5V15Z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Designaciones</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Motor de designación de árbitros para cada partido. Gestión de disponibilidad,
                        asignaciones, confirmaciones y notificaciones automáticas.
                    </p>
                </div>

                {{-- M05 Académico --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1
                                     8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906
                                     59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814
                                     m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-1.342"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Módulo Académico</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Cursos, capacitaciones, evaluaciones y seguimiento del desarrollo
                        técnico de árbitros en todas las categorías y niveles.
                    </p>
                </div>

                {{-- M06 Finanzas --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20
                                flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75
                                     M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25
                                     M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504
                                     1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0
                                     0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75
                                     A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18
                                     V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Finanzas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Control de pagos a árbitros, cuotas, ingresos y egresos del colegio.
                        Reportes financieros y conciliaciones automáticas.
                    </p>
                </div>

            </div>

            {{-- Módulos adicionales --}}
            <div class="mt-10 text-center">
                <p class="text-slate-500 text-sm">
                    Incluye además:
                    <span class="text-slate-400 font-medium">Reportes y Estadísticas</span>
                    <span class="text-slate-600 mx-2">·</span>
                    <span class="text-slate-400 font-medium">Panel Superadmin</span>
                    <span class="text-slate-600 mx-2">·</span>
                    <span class="text-slate-400 font-medium">Penas y Sanciones</span>
                </p>
            </div>
        </div>
    </section>

    {{-- ===== ¿PARA QUIÉN ES? ===== --}}
    <section id="para-quien" class="py-24 bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10
                            border border-emerald-500/20 text-emerald-400 text-sm font-medium mb-4">
                    ¿Para quién es?
                </div>
                <h2 class="text-4xl font-bold text-white mb-4">Diseñado para cada rol</h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    NovaReef adapta la experiencia y los permisos según la función de cada persona
                    dentro de la estructura del colegio de árbitros.
                </p>
            </div>

            {{-- Grid de roles --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <div class="flex gap-4 bg-slate-800 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143
                                     -6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706
                                     c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295
                                     -.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0
                                     1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111
                                     48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25
                                     2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Ejecutivo</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Visión completa de la organización, reportes gerenciales y control
                            total de la operación del colegio.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75
                                     M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25
                                     M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125
                                     1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75
                                     m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3
                                     15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12
                                     0h.008v.008H6V10.5Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Tesorero</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Gestión de pagos, cuotas, finanzas y control económico
                            completo del colegio.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1
                                     21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18
                                     0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12
                                     v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75
                                     V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5
                                     v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008
                                     v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Designador</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Asigna árbitros a los partidos, gestiona disponibilidad
                            y confirma designaciones de forma ágil.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-10 h-10 rounded-lg bg-red-500/10 border border-red-500/20
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
                                     1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Penas y Sanciones</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Registro, seguimiento y resolución de sanciones disciplinarias
                            aplicadas a árbitros dentro del colegio.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/10 border border-purple-500/20
                                flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62
                                     0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813
                                     A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814
                                     m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-1.342"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Técnico / Instructor</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Gestiona cursos, evaluaciones técnicas y el desarrollo
                            profesional de los árbitros en cada categoría.
                        </p>
                    </div>
                </div>

                {{-- Tarjeta CTA --}}
                <div class="flex flex-col justify-between bg-gradient-to-br from-emerald-900/40
                            to-teal-900/20 rounded-2xl p-6 border border-emerald-500/20">
                    <div>
                        <h3 class="font-semibold text-white mb-2 text-lg">¿Tu colegio necesita NovaReef?</h3>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">
                            Contáctanos para registrar tu colegio en la plataforma
                            o solicitar una demostración personalizada.
                        </p>
                    </div>
                    <a href="mailto:contacto@novareef.com"
                       class="inline-flex items-center gap-2 text-emerald-400 font-semibold text-sm
                              hover:text-emerald-300 transition-colors group">
                        Contactar ahora
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                             class="w-4 h-4 transition-transform group-hover:translate-x-1">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>

            </div>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer class="bg-slate-950 border-t border-white/5 py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

                {{-- Marca --}}
                <div class="col-span-1 lg:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                <path d="M2 12h20"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                        La plataforma digital para la gestión integral de colegios de árbitros
                        de fútbol en Colombia. Moderna, segura y accesible.
                    </p>
                </div>

                {{-- Plataforma --}}
                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Plataforma</h4>
                    <ul class="space-y-2.5">
                        <li>
                            <a href="#caracteristicas"
                               class="text-slate-400 hover:text-white text-sm transition-colors">
                                Características
                            </a>
                        </li>
                        <li>
                            <a href="#para-quien"
                               class="text-slate-400 hover:text-white text-sm transition-colors">
                                Roles y perfiles
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}"
                               class="text-slate-400 hover:text-white text-sm transition-colors">
                                Iniciar sesión
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Contacto --}}
                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Contacto</h4>
                    <ul class="space-y-2.5">
                        <li>
                            <a href="mailto:contacto@novareef.com"
                               class="text-slate-400 hover:text-white text-sm transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75
                                             m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25
                                             2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0
                                             1-1.07-1.916V6.75"/>
                                </svg>
                                contacto@novareef.com
                            </a>
                        </li>
                        <li>
                            <span class="text-slate-400 text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                                </svg>
                                Colombia
                            </span>
                        </li>
                    </ul>
                </div>

            </div>

            {{-- Copyright --}}
            <div class="border-t border-white/5 pt-8 flex flex-col sm:flex-row
                        items-center justify-between gap-4">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} NovaReef. Todos los derechos reservados.
                </p>
                <p class="text-slate-600 text-xs text-center">
                    Plataforma SaaS multi-tenant · Colegios de árbitros de fútbol · Colombia
                </p>
            </div>
        </div>
    </footer>


@endsection
