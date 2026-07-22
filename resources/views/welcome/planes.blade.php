    {{--  PLANES  --}}
    @if ($planes->isNotEmpty())
    <section id="planes" class="py-24 bg-slate-50 relative overflow-hidden">

        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-500/5 blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">

            <div class="text-center mb-16" data-reveal>
                <div class="section-eyebrow mb-5">
                    <i class="fa-solid fa-tags"></i>
                    Planes
                </div>
                <h2 class="font-editorial uppercase italic text-4xl sm:text-5xl font-extrabold text-slate-900 mb-4 leading-[0.95]">
                    Un plan para cada tamaño de colegio
                </h2>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    Sin permanencia mínima. Cambia de plan cuando tu colegio crezca.
                    Todos los precios en pesos colombianos (COP).
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                @foreach ($planes as $indice => $plan)
                    @php
                        $destacado = $indice === 2;
                        $modulosEtiquetas = [
                            'arbitros'      => 'Árbitros',
                            'torneos'       => 'Torneos',
                            'designaciones' => 'Designaciones',
                            'finanzas'      => 'Finanzas',
                            'academico'     => 'Académico',
                            'sanciones'     => 'Sanciones',
                        ];
                    @endphp
                    <div class="plan-card {{ $destacado ? 'plan-card--featured' : '' }} rounded-2xl p-7 flex flex-col relative"
                         data-reveal data-reveal-delay="{{ $indice * 80 }}" data-tilt>
                        @if ($destacado)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-blue-500 text-white text-[11px] font-bold uppercase tracking-wider rounded-full shadow-lg shadow-blue-500/30">
                                Recomendado
                            </span>
                        @endif

                        <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $plan->nombre }}</h3>
                        <p class="text-slate-500 text-xs mb-5">
                            {{ $plan->limiteArbitros === null ? 'Árbitros ilimitados' : 'Hasta ' . $plan->limiteArbitrosTexto . ' árbitros' }}
                        </p>

                        <div class="mb-6">
                            <span class="text-3xl font-black text-slate-900 tracking-tight">
                                ${{ number_format((float) $plan->precio, 0, ',', '.') }}
                            </span>
                            <span class="text-slate-500 text-sm">/{{ $plan->periodicidad }}</span>
                        </div>

                        <ul class="space-y-2.5 mb-7 flex-1">
                            @foreach ($plan->modulosJSON as $modulo)
                                <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                    <i class="fa-solid fa-check text-blue-500 text-xs w-3.5"></i>
                                    {{ $modulosEtiquetas[$modulo] ?? ucfirst($modulo) }}
                                </li>
                            @endforeach
                            <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                <i class="fa-solid fa-check text-blue-500 text-xs w-3.5"></i>
                                @if ($plan->limiteCuentasAdmin === null)
                                    Cuentas admin ilimitadas
                                @elseif ($plan->limiteCuentasAdmin === 1)
                                    1 cuenta admin
                                @else
                                    {{ $plan->limiteCuentasAdmin }} cuentas admin
                                @endif
                            </li>
                            @if ($plan->incluyePaginaWeb)
                                <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                    <i class="fa-solid fa-check text-blue-500 text-xs w-3.5"></i>
                                    Página web incluida
                                </li>
                            @endif
                            @if ($plan->incluyeOnboarding)
                                <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                    <i class="fa-solid fa-check text-blue-500 text-xs w-3.5"></i>
                                    Onboarding asistido
                                </li>
                            @endif
                        </ul>

                        <a href="mailto:contacto@novareef.com?subject=Quiero%20el%20plan%20{{ urlencode($plan->nombre) }}"
                           class="{{ $destacado ? 'btn-primary text-white' : 'btn-ghost text-slate-800' }} inline-flex items-center justify-center gap-2 px-5 py-3 font-semibold rounded-xl text-sm">
                            Elegir {{ $plan->nombre }}
                        </a>
                    </div>
                @endforeach
            </div>

        </div>
    </section>
    @endif

