@extends('layouts.public')

@section('contenido')

    {{--  NAVBAR  --}}
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-slate-950/80 backdrop-blur-lg transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600
                                flex items-center justify-center shadow-lg shadow-blue-500/30
                                transition-transform group-hover:scale-105">
                        <i class="fa-solid fa-futbol text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
                </a>

                {{-- Links escritorio --}}
                <nav class="hidden md:flex items-center gap-7">
                    <a href="#que-es" class="text-sm text-slate-400 hover:text-white transition-colors">Plataforma</a>
                    <a href="#caracteristicas" class="text-sm text-slate-400 hover:text-white transition-colors">Módulos</a>
                    <a href="#para-quien" class="text-sm text-slate-400 hover:text-white transition-colors">Roles</a>
                    <a href="#como-funciona" class="text-sm text-slate-400 hover:text-white transition-colors">Cómo funciona</a>
                </nav>

                {{-- CTA + Admin + hamburger --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}"
                       class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-white
                              font-semibold rounded-lg text-sm">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span class="hidden sm:inline">Iniciar sesión</span>
                    </a>

                    {{-- Acceso admin discreto --}}
                    <a href="{{ route('admin.login') }}"
                       title="Acceso administrador"
                       aria-label="Acceso administrador"
                       class="flex items-center justify-center w-9 h-9 rounded-lg
                              bg-white/5 hover:bg-white/10 text-slate-500 hover:text-blue-400
                              border border-white/10 transition-colors">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </a>

                    {{-- Hamburger móvil --}}
                    <button id="menu-toggle" aria-expanded="false" aria-label="Abrir menú"
                            class="md:hidden flex items-center justify-center w-9 h-9 rounded-lg
                                   bg-white/5 hover:bg-white/10 text-slate-300 transition-colors">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

            </div>
        </div>

        {{-- Menú móvil --}}
        <div id="mobile-menu" class="md:hidden border-t border-white/5 bg-slate-950/95 backdrop-blur-lg">
            <nav class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-1">
                <a href="#que-es" class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    <i class="fa-solid fa-circle-info text-blue-400 mr-2 w-4"></i>
                    Plataforma
                </a>
                <a href="#caracteristicas" class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    <i class="fa-solid fa-layer-group text-blue-400 mr-2 w-4"></i>
                    Módulos
                </a>
                <a href="#para-quien" class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    <i class="fa-solid fa-user-group text-blue-400 mr-2 w-4"></i>
                    Roles
                </a>
                <a href="#como-funciona" class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    <i class="fa-solid fa-list-check text-blue-400 mr-2 w-4"></i>
                    Cómo funciona
                </a>
            </nav>
        </div>
    </header>

    {{-- HERO --}}
    <section class="relative min-h-screen flex items-center pt-20 pb-16 hero-bg grid-lines overflow-hidden">

        {{-- Bandas verticales sutiles tipo árbitro --}}
        <div class="referee-stripes" aria-hidden="true"></div>

        {{-- Círculos decorativos (centro de campo) --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 rounded-full border border-blue-500/10"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[480px] h-[480px] rounded-full border border-blue-500/[0.06]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[720px] h-[720px] rounded-full border border-blue-500/[0.04]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-blue-400/40"></div>
        </div>

        {{-- Tarjetas decorativas (amarilla y roja) flotando --}}
        <div class="absolute top-32 left-8 hidden xl:block ref-card-stack fade-up fade-up-delay-3" aria-hidden="true">
            <div class="ref-card ref-card--yellow"></div>
            <div class="ref-card ref-card--red"></div>
        </div>

        {{-- Cronómetro decorativo (acento arbitral) --}}
        <div class="absolute top-40 right-12 hidden xl:flex items-center justify-center
                    w-16 h-16 rounded-2xl bg-white/[0.03] border border-white/10
                    whistle-deco fade-up fade-up-delay-4" aria-hidden="true">
            <i class="fa-solid fa-stopwatch text-blue-400/60 text-2xl"></i>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center w-full">

            {{-- Badge --}}
            <div class="section-eyebrow mb-8 fade-up">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 pulse-dot"></span>
                Plataforma SaaS · Colegios de árbitros · Colombia
            </div>

            {{-- Título --}}
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight leading-[1.05] mb-6 title-shadow fade-up fade-up-delay-1">
                <span class="text-white">El estándar digital</span><br>
                <span class="glow-text">del arbitraje moderno</span>
            </h1>

            {{-- Subtítulo --}}
            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-slate-400 leading-relaxed mb-10 fade-up fade-up-delay-2">
                NovaReef centraliza designaciones, torneos, finanzas y formación
                en una sola plataforma. Diseñada por y para colegios de árbitros que
                quieren operar con la profesionalidad del fútbol de élite.
            </p>

            {{-- Botones --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 fade-up fade-up-delay-3">
                <a href="{{ route('login') }}"
                   class="btn-primary inline-flex items-center justify-center gap-2 px-8 py-4
                          text-white font-bold rounded-xl text-base">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Iniciar sesión
                </a>
                <a href="#que-es"
                   class="btn-ghost inline-flex items-center justify-center gap-2 px-8 py-4
                          text-white font-semibold rounded-xl text-base">
                    Conocer NovaReef
                    <i class="fa-solid fa-arrow-down text-sm"></i>
                </a>
            </div>

            {{-- Estadísticas --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-3xl mx-auto fade-up fade-up-delay-4">
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-blue-400 tracking-tight">8</p>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Módulos integrados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-blue-400 tracking-tight">100%</p>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">En la nube</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-blue-400 tracking-tight">6</p>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Roles por colegio</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl sm:text-4xl font-black text-blue-400 tracking-tight">24/7</p>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Acceso desde cualquier dispositivo</p>
                </div>
            </div>

            {{-- Indicador de scroll --}}
            <div class="mt-16 flex justify-center fade-up fade-up-delay-4">
                <a href="#que-es" aria-label="Bajar"
                   class="text-slate-600 hover:text-blue-400 transition-colors animate-bounce">
                    <i class="fa-solid fa-chevron-down text-xl"></i>
                </a>
            </div>
        </div>
    </section>

    {{--  TRUST BAR  --}}
    <section class="bg-slate-950 border-y border-white/5">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-slate-500 text-sm">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-blue-400"></i>
                    <span>Datos cifrados en tránsito</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-mobile-screen text-blue-400"></i>
                    <span>Multi-dispositivo</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-nodes text-blue-400"></i>
                    <span>Multi-tenant por colegio</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-blue-400"></i>
                    <span>Actualizaciones continuas</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-flag text-blue-400"></i>
                    <span>Hecho para Colombia</span>
                </div>
            </div>
        </div>
    </section>

    {{--  ¿QUÉ ES?  --}}
    <section id="que-es" class="py-24 bg-slate-900 relative overflow-hidden">

        {{-- Glow de fondo --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-blue-500/5 blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                {{-- Texto --}}
                <div>
                    <div class="section-eyebrow mb-6">
                        <i class="fa-solid fa-circle-info"></i>
                        ¿Qué es NovaReef?
                    </div>
                    <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6 leading-[1.1]">
                        El sistema operativo de tu
                        <span class="glow-text">colegio de árbitros</span>
                    </h2>
                    <p class="text-slate-400 text-lg leading-relaxed mb-5">
                        NovaReef es una plataforma SaaS multi-tenant diseñada para digitalizar
                        la operación administrativa, deportiva y financiera de los colegios de
                        árbitros de fútbol en Colombia.
                    </p>
                    <p class="text-slate-400 text-lg leading-relaxed mb-8">
                        Desde la primera designación hasta el informe final de temporada:
                        todo conectado, todo trazable, todo en un solo lugar.
                    </p>
                    <ul class="space-y-3.5">
                        <li class="flex items-start gap-3 text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-blue-500/15 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-400 text-xs"></i>
                            </div>
                            <span>Subdominio exclusivo y aislamiento de datos por colegio</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-blue-500/15 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-400 text-xs"></i>
                            </div>
                            <span>Roles y permisos adaptados a cada función real del colegio</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-blue-500/15 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-400 text-xs"></i>
                            </div>
                            <span>Autenticación de dos factores en cuentas administrativas</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-blue-500/15 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-400 text-xs"></i>
                            </div>
                            <span>Sin instalación: 100% web, accesible desde cualquier dispositivo</span>
                        </li>
                    </ul>
                </div>

                {{-- Mockup --}}
                <div class="relative">
                    <div class="mockup-window rounded-2xl p-6 border border-white/5">

                        {{-- Barra de ventana --}}
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-3 h-3 rounded-full bg-red-500/70"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/70"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/70"></div>
                            <div class="ml-3 px-3 py-1 rounded-md bg-slate-800/50 border border-white/5 text-xs text-slate-500 font-mono flex items-center gap-2">
                                <i class="fa-solid fa-lock text-blue-400/60"></i>
                                colegio.novareef.com
                            </div>
                        </div>

                        {{-- Header del mock --}}
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <p class="text-xs text-slate-500 mb-0.5">Panel principal</p>
                                <h3 class="text-white font-bold text-sm">Colegio Antioqueño</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-500/20 border border-blue-500/30 flex items-center justify-center">
                                    <i class="fa-solid fa-user text-blue-400 text-xs"></i>
                                </div>
                            </div>
                        </div>

                        {{-- KPIs --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-slate-800/60 rounded-xl p-3.5 border border-white/5">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Árbitros activos</p>
                                    <i class="fa-solid fa-users text-blue-400/60 text-xs"></i>
                                </div>
                                <p class="text-2xl font-black text-blue-400">48</p>
                            </div>
                            <div class="bg-slate-800/60 rounded-xl p-3.5 border border-white/5">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Partidos · Semana</p>
                                    <i class="fa-solid fa-calendar-week text-amber-400/60 text-xs"></i>
                                </div>
                                <p class="text-2xl font-black text-white">12</p>
                            </div>
                        </div>

                        {{-- Designaciones --}}
                        <div class="bg-slate-800/60 rounded-xl p-4 border border-white/5 mb-3">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Próximas designaciones</p>
                                <i class="fa-solid fa-clipboard-list text-slate-500 text-xs"></i>
                            </div>
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-blue-500/20 border border-blue-500/30 flex items-center justify-center text-[10px] font-bold text-blue-400">JL</div>
                                        <div>
                                            <span class="text-sm text-slate-200 block">Jorge López</span>
                                            <span class="text-[10px] text-slate-500">Categoría A · Central</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-blue-400 bg-blue-500/10 border border-blue-500/20 px-2 py-0.5 rounded-full font-medium">Sáb 10:00</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-sky-500/20 border border-sky-500/30 flex items-center justify-center text-[10px] font-bold text-sky-400">AM</div>
                                        <div>
                                            <span class="text-sm text-slate-200 block">Ana Martínez</span>
                                            <span class="text-[10px] text-slate-500">FIFA · Central</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-sky-400 bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 rounded-full font-medium">Dom 15:30</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-[10px] font-bold text-indigo-400">CR</div>
                                        <div>
                                            <span class="text-sm text-slate-200 block">Carlos Ruiz</span>
                                            <span class="text-[10px] text-slate-500">Categoría B · Asistente</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2 py-0.5 rounded-full font-medium">Dom 17:00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Notificación --}}
                        <div class="bg-blue-500/10 rounded-xl p-3 border border-blue-500/20 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-circle-check text-blue-400 text-sm"></i>
                            </div>
                            <p class="text-sm text-blue-300 font-medium">3 pagos pendientes por procesar</p>
                        </div>
                    </div>

                    {{-- Glow detrás del mock --}}
                    <div class="absolute -inset-6 bg-blue-500/5 rounded-3xl -z-10 blur-2xl"></div>
                </div>

            </div>
        </div>
    </section>

    {{--  MÓDULOS  --}}
    <section id="caracteristicas" class="py-24 bg-slate-950 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-16">
                <div class="section-eyebrow mb-5">
                    <i class="fa-solid fa-layer-group"></i>
                    Módulos principales
                </div>
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-4 leading-tight">
                    Todo lo que tu colegio necesita
                </h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    Ocho módulos integrados que cubren cada aspecto de la operación
                    de un colegio de árbitros profesional.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- M01 Colegios --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-building-columns text-blue-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Gestión de Colegios</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Configura información institucional, suscripciones, accesos y
                        parámetros de cada colegio afiliado.
                    </p>
                </div>

                {{-- M02 Árbitros --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-id-card text-sky-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Registro de Árbitros</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Expedientes completos: datos, foto, categorías, documentos,
                        historial y estado de cada árbitro del colegio.
                    </p>
                </div>

                {{-- M03 Torneos --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-trophy text-amber-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Torneos y Competencias</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Calendario competitivo, equipos, fechas y partidos.
                        Control completo de toda la temporada.
                    </p>
                </div>

                {{-- M04 Designaciones (clave) --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-blue-500/30 card-lift relative overflow-hidden">
                    <div class="absolute top-4 right-4">
                        <span class="px-2 py-0.5 bg-blue-500/15 text-blue-400 text-[10px] font-bold uppercase tracking-wider rounded-full border border-blue-500/30">
                            Núcleo
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-calendar-days text-blue-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Designaciones</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Motor de asignación de árbitros por partido. Disponibilidad,
                        confirmaciones y notificaciones automáticas.
                    </p>
                </div>

                {{-- M05 Académico --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-graduation-cap text-purple-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Módulo Académico</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Cursos, capacitaciones y evaluaciones técnicas.
                        Seguimiento del desarrollo de cada árbitro.
                    </p>
                </div>

                {{-- M06 Finanzas --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-money-bill-trend-up text-indigo-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Finanzas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Pagos a árbitros, cuotas, ingresos y egresos.
                        Reportes y conciliaciones automatizadas.
                    </p>
                </div>

                {{-- M07 Sanciones --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-gavel text-red-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Penas y Sanciones</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Registro disciplinario, seguimiento de sanciones
                        y resoluciones aplicadas dentro del colegio.
                    </p>
                </div>

                {{-- M08 Reportes --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-chart-line text-cyan-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Reportes y Estadísticas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Indicadores de gestión, productividad de árbitros
                        y dashboards ejecutivos.
                    </p>
                </div>

                {{-- M09 Panel SuperAdmin --}}
                <div class="bg-slate-900 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-12 h-12 rounded-xl bg-slate-500/10 border border-slate-500/20 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-shield-halved text-slate-300 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">Panel SuperAdmin</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Gestión transversal de colegios, suscripciones, planes
                        y auditoría con autenticación 2FA.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{--  ¿PARA QUIÉN?  --}}
    <section id="para-quien" class="py-24 bg-slate-900 relative overflow-hidden">

        <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-blue-500/5 blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">

            <div class="text-center mb-16">
                <div class="section-eyebrow mb-5">
                    <i class="fa-solid fa-user-group"></i>
                    Roles del colegio
                </div>
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-4 leading-tight">
                    Diseñado para cada función
                </h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    NovaReef adapta la experiencia y los permisos según el rol de cada
                    persona dentro de la estructura del colegio.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-briefcase text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Ejecutivo</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Visión completa del colegio, reportes gerenciales
                            y control total de la operación.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-money-bill-wave text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Tesorero</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Gestión de pagos, cuotas, finanzas y control
                            económico completo del colegio.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-calendar-days text-amber-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Designador</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Asigna árbitros a partidos, gestiona disponibilidad
                            y confirma designaciones de forma ágil.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-gavel text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Penas y Sanciones</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Registro, seguimiento y resolución de sanciones
                            disciplinarias dentro del colegio.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-graduation-cap text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Técnico · Instructor</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Gestiona cursos, evaluaciones y el desarrollo
                            profesional de árbitros en cada categoría.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 bg-slate-800/60 rounded-2xl p-6 border border-white/5 card-lift">
                    <div class="w-11 h-11 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-flag text-sky-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-1.5">Árbitro</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Consulta tus designaciones, datos personales,
                            categoría y documentos desde cualquier lugar.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{--  CÓMO FUNCIONA  --}}
    <section id="como-funciona" class="py-24 bg-slate-950 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-16">
                <div class="section-eyebrow mb-5">
                    <i class="fa-solid fa-list-check"></i>
                    Cómo funciona
                </div>
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-4 leading-tight">
                    Empieza en cuatro pasos
                </h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    Sin instalación, sin configuraciones complejas.
                    Tu colegio listo para operar en horas, no en semanas.
                </p>
            </div>

            <div class="max-w-3xl mx-auto space-y-10">

                <div class="flex gap-5 relative">
                    <div class="step-num shrink-0">01</div>
                    <div class="step-line"></div>
                    <div class="pt-1.5">
                        <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-building-columns text-blue-400 text-sm"></i>
                            Registro del colegio
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            NovaReef crea tu colegio en la plataforma con su subdominio exclusivo,
                            categorías base y usuario administrador.
                        </p>
                    </div>
                </div>

                <div class="flex gap-5 relative">
                    <div class="step-num shrink-0">02</div>
                    <div class="step-line"></div>
                    <div class="pt-1.5">
                        <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-user-plus text-blue-400 text-sm"></i>
                            Carga de árbitros
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Registra a tus árbitros desde el panel. Cada uno recibe sus credenciales
                            por correo para completar su perfil con foto, documentos y datos personales.
                        </p>
                    </div>
                </div>

                <div class="flex gap-5 relative">
                    <div class="step-num shrink-0">03</div>
                    <div class="step-line"></div>
                    <div class="pt-1.5">
                        <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-user-group text-blue-400 text-sm"></i>
                            Roles y permisos
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Asigna roles a tu equipo administrativo: tesorero, designador, técnico,
                            sanciones. Cada uno ve solo lo que necesita.
                        </p>
                    </div>
                </div>

                <div class="flex gap-5 relative">
                    <div class="step-num shrink-0">04</div>
                    <div class="pt-1.5">
                        <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-rocket text-blue-400 text-sm"></i>
                            Operación en vivo
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Designaciones, pagos, formación y reportes. Todo el ciclo operativo
                            del colegio en una sola plataforma, accesible desde cualquier dispositivo.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{--  CTA FINAL  --}}
    <section class="py-24 bg-slate-950">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="cta-final rounded-3xl p-12 sm:p-16 text-center relative">

                <div class="relative">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-500/15 border border-blue-500/30 mb-6">
                        <i class="fa-solid fa-futbol text-blue-400 text-2xl"></i>
                    </div>

                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4 leading-tight">
                        Lleva tu colegio al
                        <span class="glow-text">siguiente nivel</span>
                    </h2>
                    <p class="text-slate-400 text-lg max-w-xl mx-auto mb-10">
                        Contáctanos para registrar tu colegio en NovaReef
                        o solicitar una demostración personalizada.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="mailto:contacto@novareef.com"
                           class="btn-primary inline-flex items-center justify-center gap-2 px-8 py-4 text-white font-bold rounded-xl">
                            <i class="fa-solid fa-envelope"></i>
                            contacto@novareef.com
                        </a>
                        <a href="{{ route('login') }}"
                           class="btn-ghost inline-flex items-center justify-center gap-2 px-8 py-4 text-white font-semibold rounded-xl">
                            Iniciar sesión
                            <i class="fa-solid fa-arrow-right text-sm"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{--  FOOTER  --}}
    <footer class="bg-slate-950 border-t border-white/5 py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

                <div class="col-span-1 lg:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600
                                    flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <i class="fa-solid fa-futbol text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xs mb-5">
                        La plataforma digital para la gestión integral de colegios
                        de árbitros de fútbol en Colombia. Moderna, segura y profesional.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="mailto:contacto@novareef.com" aria-label="Correo"
                           class="w-9 h-9 rounded-lg bg-white/5 hover:bg-blue-500/10 border border-white/5 hover:border-blue-500/20 flex items-center justify-center text-slate-400 hover:text-blue-400 transition-colors">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                        <span class="w-9 h-9 rounded-lg bg-white/5 border border-white/5 flex items-center justify-center text-slate-500">
                            <i class="fa-solid fa-flag"></i>
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Plataforma</h4>
                    <ul class="space-y-2.5">
                        <li><a href="#que-es" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">¿Qué es NovaReef?</a></li>
                        <li><a href="#caracteristicas" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Módulos</a></li>
                        <li><a href="#para-quien" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Roles</a></li>
                        <li><a href="#como-funciona" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Cómo funciona</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Acceso</h4>
                    <ul class="space-y-2.5">
                        <li>
                            <a href="{{ route('login') }}"
                               class="text-slate-400 hover:text-blue-400 text-sm transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-right-to-bracket text-xs w-4"></i>
                                Iniciar sesión
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.login') }}"
                               class="text-slate-400 hover:text-blue-400 text-sm transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-xs w-4"></i>
                                Panel SuperAdmin
                            </a>
                        </li>
                        <li>
                            <a href="mailto:contacto@novareef.com"
                               class="text-slate-400 hover:text-blue-400 text-sm transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-envelope text-xs w-4"></i>
                                contacto@novareef.com
                            </a>
                        </li>
                        <li>
                            <span class="text-slate-400 text-sm flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-xs w-4"></i>
                                Colombia
                            </span>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="divider-glow mb-6"></div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} NovaReef. Todos los derechos reservados.
                </p>
                <p class="text-slate-600 text-xs text-center sm:text-right">
                    Plataforma SaaS multi-tenant · Colegios de árbitros · Colombia
                </p>
            </div>
        </div>
    </footer>

@endsection
