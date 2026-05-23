<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — NovaReef</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #020617; }
    </style>
</head>
<body class="min-h-full text-white">

    {{-- Navbar admin --}}
    <header class="border-b border-white/5 bg-slate-950/80 backdrop-blur-lg sticky top-0 z-30">
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
                    <div>
                        <span class="text-base font-bold tracking-tight text-white">NovaReef</span>
                        <span class="ml-2 px-1.5 py-0.5 text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded font-medium">
                            Admin
                        </span>
                    </div>
                </div>

                {{-- Admin info + logout --}}
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-emerald-500/20 border border-emerald-500/30
                                    flex items-center justify-center text-xs font-bold text-emerald-400">
                            {{ strtoupper(substr(Auth::guard('admin')->user()->nombre, 0, 2)) }}
                        </div>
                        <span class="text-sm text-slate-300 font-medium">
                            {{ Auth::guard('admin')->user()->nombre }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                       text-slate-400 hover:text-white hover:bg-white/5 transition-colors border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M19 10a.75.75 0 0 0-.75-.75H8.704l1.048-.943a.75.75 0 1 0-1.004-1.114l-2.5 2.25a.75.75 0 0 0 0 1.114l2.5 2.25a.75.75 0 1 0 1.004-1.114l-1.048-.943h9.546A.75.75 0 0 0 19 10Z" clip-rule="evenodd"/>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </header>

    {{-- Contenido --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Encabezado --}}
        <div class="mb-10">
            <h1 class="text-3xl font-bold text-white mb-1">Panel de Administración</h1>
            <p class="text-slate-400 text-sm">Resumen del sistema NovaReef</p>
        </div>

        {{-- Tarjetas de estadísticas --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">

            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-400 font-medium">Total Colegios</p>
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-white">{{ $totalColegios }}</p>
                <p class="text-xs text-slate-500 mt-1">Colegios registrados</p>
            </div>

            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-400 font-medium">Colegios Activos</p>
                    <div class="w-9 h-9 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-white">{{ $colegiosActivos }}</p>
                <p class="text-xs text-slate-500 mt-1">Estado activo</p>
            </div>

            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-400 font-medium">En Trial</p>
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-white">{{ $colegiosTrial }}</p>
                <p class="text-xs text-slate-500 mt-1">Suscripciones trial</p>
            </div>

            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-400 font-medium">Total Árbitros</p>
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-bold text-white">{{ $totalArbitros }}</p>
                <p class="text-xs text-slate-500 mt-1">En todo el sistema</p>
            </div>

        </div>

    </main>

</body>
</html>
