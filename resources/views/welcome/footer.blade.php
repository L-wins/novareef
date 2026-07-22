    {{--  FOOTER — oscuro deliberado (cierre con contraste, mismo recurso que
         el mockup del hero y el CTA final ya usan dentro de la página clara) --}}
    <footer class="relative bg-gradient-to-b from-[#0b0f18] to-[#0a0d15] overflow-hidden">

        {{-- Grid + glow, mismo tratamiento que el hero --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute inset-0"
                 style="background-image:linear-gradient(rgba(255,255,255,0.02) 1px,transparent 1px),
                                        linear-gradient(90deg,rgba(255,255,255,0.02) 1px,transparent 1px);
                        background-size:48px 48px;"></div>
            <div class="absolute -top-32 left-1/4 w-[520px] h-[520px] rounded-full"
                 style="background:radial-gradient(circle,rgba(79,142,247,0.10) 0%,transparent 70%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-10">

            <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">

                <div class="sm:col-span-2 lg:col-span-2">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-9 h-9 rounded-lg overflow-hidden shadow-lg shadow-blue-500/20 flex-shrink-0">
                            <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef" class="w-full h-full object-contain">
                        </div>
                        <span class="text-lg font-bold tracking-tight">
                            <span class="text-blue-400">Nova</span><span class="text-white">Reef</span>
                        </span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                        La plataforma digital para la gestión integral de árbitros
                        de fútbol en Colombia. Moderna, segura y profesional.
                    </p>
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
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Contacto</h4>
                    <ul class="space-y-2.5">
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

                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Legal</h4>
                    <ul class="space-y-2.5">
                        <li>
                            <a href="{{ route('privacidad.politica') }}"
                               class="text-slate-400 hover:text-blue-400 text-sm transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-xs w-4"></i>
                                Política de privacidad
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('legal.terminos') }}"
                               class="text-slate-400 hover:text-blue-400 text-sm transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-file-contract text-xs w-4"></i>
                                Términos de servicio
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent mb-6"></div>

            {{-- Deslinde de responsabilidad — NovaReef es una herramienta de
                 gestión, no una entidad deportiva ni la autoridad detrás de
                 las decisiones que cada colegio registra en la plataforma. --}}
            <p class="text-slate-500 text-xs leading-relaxed max-w-4xl mb-6">
                NovaReef es una herramienta tecnológica de gestión administrativa para colegios de
                árbitros. No es una federación, liga ni autoridad deportiva, y no sustituye las
                decisiones deportivas, disciplinarias o reglamentarias que corresponden exclusivamente
                a cada colegio y a los organismos competentes del fútbol. Consulta nuestros
                <a href="{{ route('legal.terminos') }}" class="text-blue-400 hover:underline">Términos de servicio</a>
                para más detalle.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-6 border-t border-white/5">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} NovaReef. Todos los derechos reservados.
                </p>
                <p class="text-slate-600 text-xs text-center sm:text-right">
                    Plataforma SaaS multi-tenant · Colegios de árbitros · Colombia
                </p>
            </div>
        </div>
    </footer>
