    {{--  ¿QUÉ ES?  --}}
    <section id="que-es" class="py-24 bg-slate-50 relative overflow-hidden">

        {{-- Glow de fondo --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                {{-- Texto --}}
                <div data-reveal>
                    <div class="section-eyebrow mb-6">
                        <i class="fa-solid fa-circle-info"></i>
                        ¿Qué es NovaReef?
                    </div>
                    <h2 class="font-editorial uppercase italic text-4xl sm:text-5xl font-extrabold text-slate-900 mb-6 leading-[0.95]">
                        El sistema operativo de tu
                        <span class="glow-text">colegio de árbitros</span>
                    </h2>
                    <p class="text-slate-500 text-lg leading-relaxed mb-5">
                        NovaReef es una plataforma SaaS multi-tenant diseñada para digitalizar
                        la operación administrativa, deportiva y financiera de los colegios de
                        árbitros de fútbol en Colombia.
                    </p>
                    <p class="text-slate-500 text-lg leading-relaxed mb-8">
                        Desde la primera designación hasta el informe final de temporada:
                        todo conectado, todo trazable, todo en un solo lugar.
                    </p>
                    <ul class="space-y-3.5">
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="w-6 h-6 rounded-full bg-blue-500/10 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                            </div>
                            <span>Subdominio exclusivo y aislamiento de datos por colegio</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="w-6 h-6 rounded-full bg-blue-500/10 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                            </div>
                            <span>Roles y permisos adaptados a cada función real del colegio</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="w-6 h-6 rounded-full bg-blue-500/10 border border-blue-500/25 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                            </div>
                            <span>Sin instalación: 100% web, accesible desde cualquier dispositivo</span>
                        </li>
                    </ul>
                </div>

                {{-- Mockup --}}
                <div class="relative" data-reveal data-reveal-delay="150">
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
                                <h3 class="text-white font-bold text-sm">Colegio Demo</h3>
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

