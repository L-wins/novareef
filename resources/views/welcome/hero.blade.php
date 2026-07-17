    {{-- HERO --}}
    <section class="relative min-h-screen flex flex-col items-center pt-32 pb-16 hero-bg grid-lines overflow-hidden">

        {{-- Gradiente cinemático animado --}}
        <div class="absolute inset-0 hero-gradient-animated pointer-events-none" aria-hidden="true"></div>

        {{-- Bandas verticales sutiles tipo árbitro --}}
        <div class="referee-stripes" aria-hidden="true"></div>

        {{-- Círculos decorativos (centro de campo) --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 rounded-full border border-blue-500/10"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[480px] h-[480px] rounded-full border border-blue-500/[0.06]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[720px] h-[720px] rounded-full border border-blue-500/[0.04]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-blue-400/40"></div>
        </div>

        {{-- Cronómetro decorativo (acento arbitral) --}}
        <div class="absolute top-40 right-12 hidden xl:flex items-center justify-center
                    w-16 h-16 rounded-2xl bg-white/[0.03] border border-white/10
                    whistle-deco fade-up fade-up-delay-4" aria-hidden="true">
            <i class="fa-solid fa-stopwatch text-blue-400/60 text-2xl"></i>
        </div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center w-full">

            {{-- Badge --}}
            <div class="section-eyebrow mb-9 fade-up">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 pulse-dot"></span>
                Plataforma SaaS · Colegios de árbitros · Colombia
            </div>

            {{-- Título editorial --}}
            <h1 class="font-editorial font-extrabold uppercase italic
                       text-[3.4rem] sm:text-7xl lg:text-8xl leading-[0.92] mb-7
                       title-shadow fade-up fade-up-delay-1 text-balance">
                <span class="text-white/55">La plataforma que cada</span><br>
                <span class="text-white">colegio de árbitros</span><br>
                <span class="glow-text">estaba esperando</span>
            </h1>

            {{-- Subtítulo --}}
            <p class="max-w-2xl mx-auto text-lg sm:text-xl text-slate-400 leading-relaxed mb-10 fade-up fade-up-delay-2">
                NovaReef centraliza designaciones, torneos, finanzas y formación
                en una sola plataforma. Diseñada por y para colegios de árbitros que
                quieren operar con la profesionalidad del fútbol de élite.
            </p>

            {{-- Botones --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-6 fade-up fade-up-delay-3">
                <a href="{{ route('login') }}"
                   class="btn-primary inline-flex items-center justify-center gap-2 px-8 py-4
                          text-white font-bold rounded-xl text-base">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Iniciar sesión
                </a>
                <a href="#planes"
                   class="btn-ghost inline-flex items-center justify-center gap-2 px-8 py-4
                          text-white font-semibold rounded-xl text-base">
                    Ver planes y precios
                    <i class="fa-solid fa-arrow-down text-sm"></i>
                </a>
            </div>
        </div>

        {{-- ════════ DASHBOARD PREVIEW FLOTANTE ════════ --}}
        <div class="relative w-full max-w-4xl mx-auto px-4 mt-10 fade-up fade-up-delay-4" data-hero-parallax>
            <div class="rounded-2xl p-2.5 bg-white/[0.04] border border-white/10 backdrop-blur-2xl shadow-2xl shadow-black/50">
                <div class="rounded-xl overflow-hidden bg-[#0D0F1A] border border-white/5 select-none">

                    {{-- top bar --}}
                    <div class="flex items-center gap-3 px-4 py-2.5 border-b border-white/[0.07]">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500/60"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400/60"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400/60"></span>
                        </div>
                        <div class="flex-1 flex justify-center">
                            <div class="flex items-center gap-2 rounded-md px-3 py-1 bg-white/5 border border-white/[0.07] text-[11px] text-slate-500 font-mono">
                                <i class="fa-solid fa-lock text-blue-400/60 text-[9px]"></i>
                                colegio.novareef.com
                            </div>
                        </div>
                        <div class="w-6 h-6 rounded-full bg-blue-500/15 border border-blue-500/25 flex items-center justify-center text-[9px] font-bold text-blue-300">CU</div>
                    </div>

                    {{-- body --}}
                    <div class="flex text-left">

                        {{-- sidebar --}}
                        <div class="hidden sm:block w-40 shrink-0 p-3 border-r border-white/[0.07]">
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium mb-1 bg-white/[0.08] text-white">
                                <i class="fa-solid fa-house text-[10px]"></i> Inicio
                            </div>
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium mb-1 text-slate-500">
                                <i class="fa-solid fa-user-group text-[10px]"></i> Árbitros
                            </div>
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium mb-1 text-slate-500">
                                <i class="fa-solid fa-trophy text-[10px]"></i> Torneos
                            </div>
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium mb-1 bg-blue-500/10 text-blue-300">
                                <i class="fa-solid fa-calendar-days text-[10px]"></i> Designaciones
                                <span class="ml-auto text-[9px] bg-amber-400/15 text-amber-300 rounded-full px-1.5">7</span>
                            </div>
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium mb-1 text-slate-500">
                                <i class="fa-solid fa-money-bill-wave text-[10px]"></i> Finanzas
                            </div>
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[11px] font-medium text-slate-500">
                                <i class="fa-solid fa-chart-line text-[10px]"></i> Reportes
                            </div>
                        </div>

                        {{-- main --}}
                        <div class="flex-1 p-4 bg-[#12141F]">
                            <p class="text-[12px] font-semibold text-white mb-3">Bienvenido, Colegio Cundinamarca</p>

                            <div class="grid grid-cols-2 gap-2.5 mb-3">
                                <div class="rounded-lg p-3 bg-[#1A1D2E] border border-white/[0.07]">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] text-slate-500">Árbitros activos</span>
                                        <i class="fa-solid fa-arrow-trend-up text-[10px] text-emerald-400"></i>
                                    </div>
                                    <p class="font-editorial font-extrabold text-2xl text-white leading-none">48</p>
                                    <p class="text-[9px] text-emerald-400 mt-1">+4 este mes</p>
                                </div>
                                <div class="rounded-lg p-3 bg-[#1A1D2E] border border-white/[0.07]">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] text-slate-500">Designaciones</span>
                                        <i class="fa-solid fa-ellipsis text-[10px] text-slate-600"></i>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] py-0.5">
                                        <span class="text-slate-500">Completadas</span><span class="font-bold text-emerald-400">89</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] py-0.5">
                                        <span class="text-slate-500">Pendientes</span><span class="font-bold text-amber-400">31</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg overflow-hidden border border-white/[0.07]">
                                <div class="flex justify-between px-3 py-1.5 border-b border-white/[0.07]">
                                    <span class="text-[10px] font-semibold text-slate-300">Últimas designaciones</span>
                                    <span class="text-[9px] text-slate-600">Ver todas</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-1.5 text-[10px] border-b border-white/[0.05]">
                                    <span class="text-slate-300">Rincón FC vs Real Chía</span>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-400/10 text-emerald-400 text-[9px] font-medium">Confirmada</span>
                                </div>
                                <div class="flex items-center justify-between px-3 py-1.5 text-[10px]">
                                    <span class="text-slate-300">Bogotá FC vs Tenjo Atl.</span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-400/10 text-amber-400 text-[9px] font-medium">Pendiente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- glow detrás del mockup --}}
            <div class="absolute -inset-8 bg-blue-500/10 rounded-[2rem] -z-10 blur-3xl"></div>
        </div>

        {{-- Indicador de scroll --}}
        <div class="relative mt-10 flex justify-center fade-up fade-up-delay-4">
            <a href="#que-es" aria-label="Bajar"
               class="text-slate-600 hover:text-blue-400 transition-colors animate-bounce">
                <i class="fa-solid fa-chevron-down text-xl"></i>
            </a>
        </div>
    </section>

