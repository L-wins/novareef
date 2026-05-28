<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta suspendida — NovaReef</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #020617; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4">

    <div class="w-full max-w-md text-center">

        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-10">
            <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    <path d="M2 12h20"/>
                </svg>
            </div>
            <span class="text-xl font-bold text-white tracking-tight">NovaReef</span>
        </div>

        {{-- Ícono de advertencia --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" class="w-10 h-10 text-red-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-white mb-3">Tu cuenta ha sido suspendida</h1>
        <p class="text-slate-400 leading-relaxed mb-2">
            El acceso al colegio
            @if(isset($colegio))
                <span class="text-slate-300 font-medium">{{ $colegio->nombreColegio }}</span>
            @endif
            ha sido suspendido temporalmente.
        </p>
        <p class="text-slate-500 text-sm mb-10">
            Contacta a NovaReef para más información sobre el estado de tu cuenta.
        </p>

        {{-- Contacto --}}
        <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 mb-8 text-left">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Canales de soporte</p>
            <a href="mailto:soporte@novareef.com"
               class="flex items-center gap-3 text-sm text-slate-300 hover:text-white transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20
                            flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </div>
                soporte@novareef.com
            </a>
        </div>

        {{-- Cerrar sesión --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10
                           border border-white/10 text-slate-300 hover:text-white text-sm font-medium
                           transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z" clip-rule="evenodd"/>
                    <path fill-rule="evenodd" d="M19 10a.75.75 0 0 0-.75-.75H8.704l1.048-.943a.75.75 0 1 0-1.004-1.114l-2.5 2.25a.75.75 0 0 0 0 1.114l2.5 2.25a.75.75 0 1 0 1.004-1.114l-1.048-.943h9.546A.75.75 0 0 0 19 10Z" clip-rule="evenodd"/>
                </svg>
                Cerrar sesión
            </button>
        </form>

    </div>

</body>
</html>
