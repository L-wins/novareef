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
                <i class="fa-solid fa-futbol text-white text-base"></i>
            </div>
            <span class="text-xl font-bold text-white tracking-tight">NovaReef</span>
        </div>

        {{-- Ícono de advertencia --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-3xl"></i>
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
                    <i class="fa-solid fa-envelope text-emerald-400 text-sm"></i>
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
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </button>
        </form>

    </div>

</body>
</html>
