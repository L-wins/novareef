<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo no incluido — NovaReef</title>
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

        {{-- Ícono --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                <i class="fa-solid fa-lock text-blue-400 text-3xl"></i>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-white mb-3">Módulo no incluido en tu plan</h1>
        <p class="text-slate-400 leading-relaxed mb-10">
            Tu plan actual no incluye este módulo. Actualiza tu plan para acceder a esta funcionalidad.
        </p>

        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10
                  border border-white/10 text-slate-300 hover:text-white text-sm font-medium
                  transition-colors duration-200">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al panel
        </a>

    </div>

</body>
</html>
