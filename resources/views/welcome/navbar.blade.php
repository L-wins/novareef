    {{--  NAVBAR  --}}
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-slate-950/80 backdrop-blur-lg transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('welcome') }}"
                   class="flex items-center gap-2.5 group rounded-2xl pl-2 pr-4 py-2
                          bg-white/[0.06] border border-white/10 backdrop-blur-lg
                          transition-colors hover:bg-white/[0.09]">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600
                                flex items-center justify-center shadow-lg shadow-blue-500/30
                                transition-transform group-hover:scale-105">
                        <i class="fa-solid fa-futbol text-white text-sm"></i>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-white">NovaReef</span>
                </a>

                {{-- Links escritorio --}}
                <nav class="hidden md:flex items-center gap-7">
                    <a href="#que-es" class="text-sm text-slate-400 hover:text-white transition-colors">Plataforma</a>
                    <a href="#caracteristicas" class="text-sm text-slate-400 hover:text-white transition-colors">Módulos</a>
                    <a href="#planes" class="text-sm text-slate-400 hover:text-white transition-colors">Precios</a>
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
                <a href="#planes" class="px-4 py-2.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/5 transition-colors text-sm">
                    <i class="fa-solid fa-tags text-blue-400 mr-2 w-4"></i>
                    Precios
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

