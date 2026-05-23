<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA — NovaReef Admin</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #020617; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4">

    <div class="w-full max-w-sm">

        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-8">
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

        {{-- Card --}}
        <div class="bg-slate-900 border border-white/10 rounded-2xl p-8 shadow-2xl">

            {{-- Ícono de seguridad --}}
            <div class="flex justify-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" class="w-7 h-7 text-emerald-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0
                                 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-xl font-bold text-white text-center mb-1">Verificación en dos pasos</h1>
            <p class="text-sm text-slate-400 text-center mb-7">
                Ingresa el código de tu aplicación Google Authenticator
            </p>

            {{-- Errores --}}
            @if ($errors->any())
                <div class="mb-5 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/25 text-red-400 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.2fa.post') }}">
                @csrf

                <div class="mb-6">
                    <label for="code" class="block text-sm font-medium text-slate-300 mb-2">
                        Código de autenticación
                    </label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        autofocus
                        placeholder="000000"
                        class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-white/10 text-white text-center
                               text-2xl tracking-[0.5em] font-mono placeholder:text-slate-600
                               focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50
                               transition-colors"
                        value="{{ old('code') }}"
                    >
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-emerald-500 hover:bg-emerald-400 text-white font-semibold
                               rounded-xl transition-colors duration-200 flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                    </svg>
                    Verificar código
                </button>
            </form>

        </div>

        <p class="text-center text-slate-600 text-xs mt-6">
            ¿Problemas para acceder?
            <a href="{{ route('welcome') }}" class="text-slate-500 hover:text-slate-400 transition-colors underline">
                Volver al inicio
            </a>
        </p>

    </div>

</body>
</html>
