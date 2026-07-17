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
                        <li><a href="#planes" class="text-slate-400 hover:text-blue-400 text-sm transition-colors">Precios</a></li>
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
