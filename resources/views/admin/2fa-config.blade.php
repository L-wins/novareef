<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA — NovaReef Admin</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #020617; }
        .qr-container svg { display: block; border-radius: 12px; }
    </style>
</head>
<body class="min-h-full text-white">

    {{-- Navbar --}}
    <header class="border-b border-white/5 bg-slate-950/80 backdrop-blur-lg sticky top-0 z-30">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            <path d="M2 12h20"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold text-white">NovaReef</span>
                    <span class="text-slate-500">/</span>
                    <span class="text-sm text-slate-400">Autenticación de dos factores</span>
                </div>

                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                          text-slate-400 hover:text-white hover:bg-white/5 transition-colors border border-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd"
                              d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75
                                 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
                              clip-rule="evenodd"/>
                    </svg>
                    Panel
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-12">

        {{-- Mensajes de sesión --}}
        @if(session('success'))
        <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483
                         4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Encabezado --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white mb-1">Autenticación de dos factores</h1>
            <p class="text-slate-400 text-sm">
                Añade una capa extra de seguridad a tu cuenta de administrador.
            </p>
        </div>

        @if($activo)
            {{-- ══ ESTADO ACTIVO ══ --}}
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-8">

                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 rounded-xl bg-emerald-500/10 border border-emerald-500/20
                                flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="#10b981" stroke-width="1.8" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6
                                     11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332
                                     9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196
                                     0-6.1-1.248-8.25-3.285Z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h2 class="text-lg font-semibold text-white">2FA Activado</h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Activo
                            </span>
                        </div>
                        <p class="text-sm text-slate-400">
                            Tu cuenta está protegida con autenticación de dos factores.
                        </p>
                    </div>
                </div>

                <div class="border-t border-white/5 pt-6">
                    <p class="text-sm text-slate-400 mb-4">
                        Para desactivar el 2FA ingresa tu contraseña actual como confirmación.
                    </p>

                    <form method="POST" action="{{ route('admin.2fa.disable') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf

                        <div class="flex-1">
                            <input type="password" name="password"
                                   placeholder="Contraseña actual"
                                   class="w-full px-4 py-2.5 rounded-xl bg-white/5 border
                                          {{ $errors->has('password') ? 'border-red-500/60' : 'border-white/10' }}
                                          text-white placeholder-slate-500 text-sm focus:outline-none
                                          focus:border-white/25 focus:bg-white/8 transition-colors">
                            @error('password')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl text-sm font-medium bg-red-500/10 text-red-400
                                       border border-red-500/20 hover:bg-red-500/20 transition-colors whitespace-nowrap">
                            Desactivar 2FA
                        </button>
                    </form>
                </div>
            </div>

        @else
            {{-- ══ ESTADO INACTIVO — ACTIVACIÓN ══ --}}
            <div class="grid sm:grid-cols-2 gap-6">

                {{-- Panel izquierdo: QR --}}
                <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                    <h2 class="text-base font-semibold text-white mb-1">Escanea el código QR</h2>
                    <p class="text-xs text-slate-400 mb-5">
                        Abre Google Authenticator, Microsoft Authenticator u otra app TOTP
                        y escanea este código.
                    </p>

                    {{-- QR SVG --}}
                    <div class="qr-container flex justify-center mb-5
                                p-3 bg-white rounded-xl">
                        {!! $qrSvg !!}
                    </div>

                    {{-- Clave manual --}}
                    <div>
                        <p class="text-xs text-slate-500 mb-1.5">
                            ¿No puedes escanear? Ingresa esta clave manualmente:
                        </p>
                        <code class="block w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10
                                     text-xs font-mono text-emerald-400 tracking-widest text-center select-all">
                            {{ $secret }}
                        </code>
                    </div>
                </div>

                {{-- Panel derecho: instrucciones + formulario --}}
                <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 flex flex-col">
                    <h2 class="text-base font-semibold text-white mb-4">Verificar y activar</h2>

                    {{-- Instrucciones --}}
                    <ol class="space-y-3 mb-6 flex-1">
                        <li class="flex gap-3 text-sm text-slate-400">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-slate-800 border border-white/10
                                         flex items-center justify-center text-xs text-slate-300 font-medium">1</span>
                            <span>Descarga <strong class="text-slate-200">Google Authenticator</strong> o cualquier app TOTP.</span>
                        </li>
                        <li class="flex gap-3 text-sm text-slate-400">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-slate-800 border border-white/10
                                         flex items-center justify-center text-xs text-slate-300 font-medium">2</span>
                            <span>Escanea el código QR con la app.</span>
                        </li>
                        <li class="flex gap-3 text-sm text-slate-400">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-slate-800 border border-white/10
                                         flex items-center justify-center text-xs text-slate-300 font-medium">3</span>
                            <span>Ingresa el código de <strong class="text-slate-200">6 dígitos</strong> que muestra la app para confirmar.</span>
                        </li>
                    </ol>

                    {{-- Formulario --}}
                    <form method="POST" action="{{ route('admin.2fa.enable') }}">
                        @csrf

                        <label class="block text-xs text-slate-400 mb-1.5">
                            Código de verificación
                        </label>
                        <input type="text" name="codigo"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               autocomplete="one-time-code"
                               placeholder="123456"
                               class="w-full px-4 py-3 rounded-xl bg-white/5 border
                                      {{ $errors->has('codigo') ? 'border-red-500/60' : 'border-white/10' }}
                                      text-white placeholder-slate-600 text-lg font-mono tracking-widest
                                      text-center focus:outline-none focus:border-emerald-500/40
                                      focus:bg-white/8 transition-colors mb-1.5">
                        @error('codigo')
                            <p class="text-xs text-red-400 mb-3">{{ $message }}</p>
                        @else
                            <p class="text-xs text-slate-600 mb-3">El código cambia cada 30 segundos.</p>
                        @enderror

                        <button type="submit"
                                class="w-full py-2.5 rounded-xl text-sm font-medium bg-emerald-500 text-white
                                       hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-500/20">
                            Activar 2FA
                        </button>
                    </form>
                </div>

            </div>

            {{-- Advertencia --}}
            <div class="mt-4 flex items-start gap-3 px-4 py-3 rounded-xl bg-amber-500/8 border border-amber-500/20 text-sm text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5">
                    <path fill-rule="evenodd"
                          d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17
                             2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485
                             2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75
                             0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                          clip-rule="evenodd"/>
                </svg>
                <span>
                    Guarda la clave secreta en un lugar seguro. Si pierdes acceso a tu app de autenticación
                    y no tienes la clave, necesitarás intervención manual en la base de datos.
                </span>
            </div>
        @endif

    </main>

</body>
</html>
