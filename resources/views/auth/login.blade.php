@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('contenido')

    {{-- Círculos decorativos de fondo --}}
    <div class="fixed inset-0 pointer-events-none select-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-emerald-500/[0.06]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-emerald-500/[0.04]"></div>
    </div>

    <div class="relative w-full max-w-md">

        {{-- Logo y encabezado --}}
        <div class="text-center mb-8">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-3 mb-6 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center
                            shadow-lg shadow-emerald-500/30 transition-transform group-hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" class="w-6 h-6 text-white">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        <path d="M2 12h20"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">NovaReef</span>
            </a>
            <h1 class="text-2xl font-bold text-white">Bienvenido de nuevo</h1>
            <p class="text-slate-400 mt-1 text-sm">Ingresa tus credenciales para acceder al panel</p>
        </div>

        {{-- Tarjeta del formulario --}}
        <div id="login-card"
             class="bg-slate-900 rounded-2xl border border-white/5 shadow-2xl shadow-black/40 p-8"
             data-has-error="{{ $errors->isNotEmpty() ? 'true' : 'false' }}">

            {{-- Alerta de error de credenciales --}}
            @if ($errors->has('email'))
                <div class="alert-error mb-6" role="alert">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
                                 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <span>{{ $errors->first('email') }}</span>
                </div>
            @endif

            {{-- Formulario --}}
            <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                {{-- Campo: Correo electrónico --}}
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Correo electrónico
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="tu@correo.com"
                        class="field-input {{ $errors->has('email') ? 'has-error' : '' }}"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Campo: Contraseña --}}
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Contraseña
                    </label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="field-input {{ $errors->has('password') ? 'has-error' : '' }}"
                        >
                        <button type="button" id="toggle-password"
                                class="toggle-password" aria-label="Mostrar contraseña">
                            {{-- Ícono "mostrar" (ojo abierto) --}}
                            <svg data-icon="show" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007
                                         9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007
                                         -9.963-7.178Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            {{-- Ícono "ocultar" (ojo tachado) --}}
                            <svg data-icon="hide" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" class="w-4 h-4 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5
                                         c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0
                                         8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228
                                         3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243
                                         -4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Recordarme --}}
                <div class="flex items-start gap-2.5 mb-6">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                           class="custom-checkbox"
                           {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember" class="text-sm text-slate-400 cursor-pointer select-none leading-relaxed">
                        Recordarme en este dispositivo
                    </label>
                </div>

                {{-- Botón de envío --}}
                <button type="submit" id="btn-login" class="btn-login">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd"
                                  d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75
                                     0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z"
                                  clip-rule="evenodd"/>
                        </svg>
                        Iniciar sesión
                    </span>
                </button>
            </form>

        </div>

        {{-- Enlace "Volver" --}}
        <div class="text-center mt-6">
            <a href="{{ route('welcome') }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-300
                      transition-colors duration-200 group">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                     class="w-4 h-4 transition-transform group-hover:-translate-x-0.5">
                    <path fill-rule="evenodd"
                          d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75
                             0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z"
                          clip-rule="evenodd"/>
                </svg>
                Volver a la página principal
            </a>
        </div>

    </div>

@endsection
