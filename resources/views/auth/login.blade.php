@extends('layouts.auth')

@section('body_class', 'bg-slate-950 text-white antialiased min-h-screen flex')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="flex w-full min-h-screen">

    {{-- ══ PANEL IZQUIERDO — BRANDING ══ --}}
    <div class="hidden lg:flex lg:w-[52%] bg-slate-900 border-r border-white/5
                flex-col justify-between p-14 relative overflow-hidden">

        {{-- Decoración de fondo --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full"
                 style="background:radial-gradient(circle,rgba(16,185,129,0.10) 0%,transparent 65%);"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full"
                 style="background:radial-gradient(circle,rgba(20,184,166,0.06) 0%,transparent 65%);"></div>
            <div class="absolute inset-0"
                 style="background-image:linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),
                                        linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px);
                        background-size:48px 48px;"></div>
        </div>

        <div class="relative z-10">

            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-16">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center
                            shadow-lg shadow-emerald-500/30 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        <path d="M2 12h20"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
            </div>

            {{-- Título --}}
            <h1 class="text-4xl font-black tracking-tight leading-tight text-white mb-4">
                Gestión moderna<br>
                <span class="text-emerald-400">para árbitros de élite</span>
            </h1>
            <p class="text-slate-400 text-base leading-relaxed mb-12 max-w-sm">
                La plataforma SaaS que centraliza designaciones, torneos,
                finanzas y formación en un solo lugar.
            </p>

            {{-- Features --}}
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20
                                flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1
                                     21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18
                                     0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                    </div>
                    <span class="text-slate-300 text-sm">Designaciones automáticas por partido</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-500/10 border border-teal-500/20
                                flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1
                                     14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                    </div>
                    <span class="text-slate-300 text-sm">Expedientes completos de árbitros</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20
                                flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75
                                     M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25
                                     M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504
                                     1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0
                                     0-.75.75v.75m0 0H3.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </div>
                    <span class="text-slate-300 text-sm">Control financiero del colegio</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20
                                flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <span class="text-slate-300 text-sm">Roles y permisos por función</span>
                </div>
            </div>

        </div>

        <p class="relative z-10 text-xs text-slate-600">
            Plataforma SaaS · Colegios de árbitros · Colombia
        </p>
    </div>

    {{-- ══ PANEL DERECHO — FORMULARIO ══ --}}
    <div class="flex-1 flex items-center justify-center p-8 lg:p-16 bg-slate-950">
        <div class="w-full max-w-md">

            {{-- Logo móvil --}}
            <div class="flex lg:hidden items-center justify-center gap-3 mb-10">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center
                            shadow-lg shadow-emerald-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" class="w-5 h-5 text-white">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        <path d="M2 12h20"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
            </div>

            <h2 class="text-2xl font-bold text-white mb-1">Bienvenido de nuevo</h2>
            <p class="text-slate-400 text-sm mb-8">Ingresa tus credenciales para acceder al panel</p>

            {{-- Tarjeta --}}
            <div id="login-card"
                 class="bg-slate-900 rounded-2xl border border-white/5 shadow-2xl shadow-black/40 p-8"
                 data-has-error="{{ $errors->isNotEmpty() ? 'true' : 'false' }}">

                {{-- Error de credenciales --}}
                @if ($errors->has('emailUsuario'))
                    <div class="alert-error mb-6" role="alert">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
                                     1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <span>{{ $errors->first('emailUsuario') }}</span>
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Correo --}}
                    <div class="mb-5">
                        <label for="emailUsuario"
                               class="block text-sm font-medium text-slate-300 mb-1.5">
                            Correo electrónico
                        </label>
                        <input type="email"
                               id="emailUsuario"
                               name="emailUsuario"
                               value="{{ old('emailUsuario') }}"
                               required
                               autocomplete="email"
                               autofocus
                               placeholder="tu@correo.com"
                               class="field-input {{ $errors->has('emailUsuario') ? 'has-error' : '' }}">
                        @error('emailUsuario')
                            <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contraseña --}}
                    <div class="mb-5">
                        <label for="password"
                               class="block text-sm font-medium text-slate-300 mb-1.5">
                            Contraseña
                        </label>
                        <div class="password-wrapper">
                            <input type="password"
                                   id="password"
                                   name="passwordUsuario"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="field-input {{ $errors->has('passwordUsuario') ? 'has-error' : '' }}">
                            <button type="button" id="toggle-password"
                                    class="toggle-password" aria-label="Mostrar contraseña">
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
                        @error('passwordUsuario')
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
                        <label for="remember"
                               class="text-sm text-slate-400 cursor-pointer select-none leading-relaxed">
                            Recordarme en este dispositivo
                        </label>
                    </div>

                    <button type="submit" id="btn-login" class="btn-login">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="btn-text flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 fill="currentColor" class="w-4 h-4">
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

            {{-- Volver --}}
            <div class="text-center mt-6">
                <a href="{{ route('welcome') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-slate-500
                          hover:text-slate-300 transition-colors duration-200 group">
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
    </div>

</div>
@endsection
