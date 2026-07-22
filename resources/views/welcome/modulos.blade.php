    {{--  MÓDULOS — CINTA ANIMADA CON TARJETAS DESPLEGABLES  --}}
    <section id="caracteristicas" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-16" data-reveal>
                <div class="section-eyebrow mb-5">
                    <i class="fa-solid fa-layer-group"></i>
                    Módulos principales
                </div>
                <h2 class="font-editorial uppercase italic text-4xl sm:text-5xl font-extrabold text-slate-900 mb-4 leading-[0.95]">
                    Todo lo que tu colegio necesita
                </h2>
                <p class="text-slate-500 text-lg max-w-2xl mx-auto">
                    Siete módulos integrados que cubren cada aspecto de la operación
                    de un colegio de árbitros profesional.
                </p>
            </div>

        </div>

        {{-- Cinta horizontal infinita — se detiene con el cursor encima,
             se despliega la descripción con clic. El contenido se duplica
             una vez para que la animación haga un loop sin salto visible. --}}
        @php
            $modulos = [
                ['color' => 'blue',   'icon' => 'fa-calendar-days',      'titulo' => 'Designaciones',        'badge' => 'Núcleo', 'desc' => 'Motor de asignación de árbitros por partido, con disponibilidad en tiempo real, confirmaciones automáticas, publicación, veedor y acta digital.'],
                ['color' => 'sky',    'icon' => 'fa-id-card',            'titulo' => 'Registro de Árbitros',  'desc' => 'Expedientes completos: datos, foto, categorías, documentos e historial.'],
                ['color' => 'indigo', 'icon' => 'fa-money-bill-trend-up','titulo' => 'Finanzas',              'desc' => 'Pagos a árbitros, cuotas, ingresos y egresos con conciliación automática.'],
                ['color' => 'amber',  'icon' => 'fa-trophy',             'titulo' => 'Torneos',               'desc' => 'Calendario competitivo, equipos, fechas y partidos de toda la temporada.'],
                ['color' => 'purple', 'icon' => 'fa-graduation-cap',     'titulo' => 'Académico',             'desc' => 'Sesiones, asistencia en tiempo real y justificaciones de cada árbitro.'],
                ['color' => 'red',    'icon' => 'fa-gavel',              'titulo' => 'Sanciones',             'desc' => 'Registro disciplinario, multas y resoluciones dentro del colegio.'],
                ['color' => 'cyan',   'icon' => 'fa-chart-line',         'titulo' => 'Reportes',              'desc' => 'Indicadores de gestión y dashboards ejecutivos exportables.'],
            ];
        @endphp

        <div class="modulos-marquee" data-reveal data-reveal-delay="80">
            <div class="modulos-marquee__track" data-modulos-track>
                @for ($vuelta = 0; $vuelta < 2; $vuelta++)
                    @foreach ($modulos as $modulo)
                        <button type="button"
                                class="modulos-card modulos-card--{{ $modulo['color'] }}"
                                data-modulos-card
                                @if ($vuelta === 1) aria-hidden="true" tabindex="-1" @endif>
                            <div class="modulos-card__top">
                                <div class="modulos-card__top-left">
                                    <div class="modulos-card__icon">
                                        <i class="fa-solid {{ $modulo['icon'] }}"></i>
                                    </div>
                                    @if (isset($modulo['badge']))
                                        <span class="modulos-card__badge">{{ $modulo['badge'] }}</span>
                                    @endif
                                </div>
                                <i class="fa-solid fa-chevron-down modulos-card__chevron"></i>
                            </div>
                            <h3 class="modulos-card__title">{{ $modulo['titulo'] }}</h3>
                            <div class="modulos-card__desc">
                                <p>{{ $modulo['desc'] }}</p>
                            </div>
                        </button>
                    @endforeach
                @endfor
            </div>
        </div>
    </section>

