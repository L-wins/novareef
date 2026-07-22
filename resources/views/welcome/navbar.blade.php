    {{--  NAVBAR  --}}
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 border-b border-slate-200 bg-white/80 backdrop-blur-lg transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('welcome') }}"
                   class="flex items-center group rounded-2xl pl-1 pr-3 py-1
                          bg-slate-900/[0.03] border border-slate-900/[0.06]
                          transition-colors hover:bg-slate-900/[0.06]">
                    <img src="{{ asset('images/logo/novareef-logo-light.png') }}" alt="NovaReef"
                         class="h-12 w-auto -my-1 rounded-lg transition-transform group-hover:scale-105">
                </a>

                {{-- Links escritorio --}}
                <nav class="hidden md:flex items-center gap-7">
                    <a href="{{ route('welcome') }}#que-es" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Plataforma</a>
                    <a href="{{ route('welcome') }}#caracteristicas" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Módulos</a>
                    <a href="{{ route('welcome') }}#planes" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Precios</a>
                    <a href="{{ route('welcome') }}#para-quien" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Roles</a>
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
                              bg-slate-900/[0.04] hover:bg-slate-900/[0.08] text-slate-400 hover:text-blue-500
                              border border-slate-900/[0.06] transition-colors">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </a>

                    {{-- Hamburger móvil --}}
                    <button id="menu-toggle" aria-expanded="false" aria-label="Abrir menú"
                            class="md:hidden flex items-center justify-center w-9 h-9 rounded-lg
                                   bg-slate-900/[0.04] hover:bg-slate-900/[0.08] text-slate-600 transition-colors">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

            </div>
        </div>

        {{-- Menú móvil --}}
        <div id="mobile-menu" class="md:hidden border-t border-slate-200 bg-white/95 backdrop-blur-lg">
            <nav class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-1">
                <a href="{{ route('welcome') }}#que-es" class="px-4 py-2.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-900/[0.04] transition-colors text-sm">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-2 w-4"></i>
                    Plataforma
                </a>
                <a href="{{ route('welcome') }}#caracteristicas" class="px-4 py-2.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-900/[0.04] transition-colors text-sm">
                    <i class="fa-solid fa-layer-group text-blue-500 mr-2 w-4"></i>
                    Módulos
                </a>
                <a href="{{ route('welcome') }}#planes" class="px-4 py-2.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-900/[0.04] transition-colors text-sm">
                    <i class="fa-solid fa-tags text-blue-500 mr-2 w-4"></i>
                    Precios
                </a>
                <a href="{{ route('welcome') }}#para-quien" class="px-4 py-2.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-900/[0.04] transition-colors text-sm">
                    <i class="fa-solid fa-user-group text-blue-500 mr-2 w-4"></i>
                    Roles
                </a>
            </nav>
        </div>
    </header>

