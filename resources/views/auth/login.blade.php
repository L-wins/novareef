@extends('layouts.auth')

@section('body_class', 'bg-slate-950 text-white antialiased min-h-screen flex')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="flex w-full min-h-screen">

    {{-- ═══ PANEL IZQUIERDO — BRANDING ═══ --}}
    <div class="hidden lg:flex lg:w-[52%] bg-slate-900 border-r border-white/5
                flex-col justify-between p-14 relative overflow-hidden">

        {{-- Decoración de fondo --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full"
                 style="background:radial-gradient(circle,rgba(79,142,247,0.12) 0%,transparent 65%);"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full"
                 style="background:radial-gradient(circle,rgba(56,189,248,0.08) 0%,transparent 65%);"></div>

            {{-- Círculos de cancha --}}
            <div class="login-circle" style="width:480px;height:480px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.6;"></div>
            <div class="login-circle" style="width:720px;height:720px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.4;"></div>

            {{-- Bandas referee sutiles --}}
            <div class="login-stripes"></div>

            {{-- Grid --}}
            <div class="absolute inset-0"
                 style="background-image:linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),
                                        linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px);
                        background-size:48px 48px;"></div>
        </div>

        {{-- Tarjetas decorativas amarilla/roja --}}
        <div class="absolute top-12 right-12 hidden xl:flex items-end gap-2 login-fade login-fade-4" aria-hidden="true">
            <div class="deco-ref-card deco-ref-card--yellow" style="transform:rotate(-8deg);"></div>
            <div class="deco-ref-card deco-ref-card--red" style="transform:rotate(6deg);margin-left:-6px;"></div>
        </div>

        <div class="relative z-10">

            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-14 login-fade login-fade-1">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600
                            flex items-center justify-center shadow-lg shadow-blue-500/30 flex-shrink-0">
                    <i class="fa-solid fa-futbol text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
            </div>

            {{-- Título --}}
            <div class="login-fade login-fade-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                            bg-blue-500/10 border border-blue-500/20 text-blue-400
                            text-xs font-semibold mb-6 uppercase tracking-wider">
                    <i class="fa-solid fa-shield-halved text-[10px]"></i>
                    Plataforma de colegios
                </div>
                <h1 class="text-4xl font-black tracking-tight leading-[1.05] text-white mb-4">
                    El estándar digital<br>
                    <span class="bg-gradient-to-r from-blue-400 to-sky-400 bg-clip-text text-transparent">
                        del arbitraje moderno
                    </span>
                </h1>
                <p class="text-slate-400 text-base leading-relaxed mb-12 max-w-sm">
                    Designaciones, torneos, finanzas y formación
                    en una sola plataforma profesional.
                </p>
            </div>

            {{-- Features --}}
            <div class="space-y-3.5 login-fade login-fade-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-calendar-days text-blue-400 text-sm"></i>
                    </div>
                    <span class="text-slate-300 text-sm">Designaciones por partido y categoría</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-id-card text-sky-400 text-sm"></i>
                    </div>
                    <span class="text-slate-300 text-sm">Expedientes completos de árbitros</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-money-bill-trend-up text-indigo-400 text-sm"></i>
                    </div>
                    <span class="text-slate-300 text-sm">Control financiero del colegio</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-user-group text-purple-400 text-sm"></i>
                    </div>
                    <span class="text-slate-300 text-sm">Roles y permisos por función</span>
                </div>
            </div>

        </div>

        <p class="relative z-10 text-xs text-slate-600 login-fade login-fade-4">
            <i class="fa-solid fa-flag mr-1.5 text-blue-500/60"></i>
            Plataforma SaaS · Colegios de árbitros · Colombia
        </p>
    </div>

    {{-- ═══ PANEL DERECHO — FORMULARIO ═══ --}}
    <div class="flex-1 flex items-center justify-center p-8 lg:p-16 bg-slate-950 relative overflow-hidden">

        {{-- Gradiente sutil de fondo --}}
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-30"
                 style="background:radial-gradient(circle,rgba(79,142,247,0.08) 0%,transparent 65%);"></div>
        </div>

        <div class="w-full max-w-md relative">

            {{-- Logo móvil --}}
            <div class="flex lg:hidden items-center justify-center gap-3 mb-10 login-fade login-fade-1">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600
                            flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-futbol text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
            </div>

            <div class="login-fade login-fade-2">
                <h2 class="text-3xl font-bold text-white mb-1.5 tracking-tight">Bienvenido de nuevo</h2>
                <p class="text-slate-400 text-sm mb-8">Ingresa tus credenciales para acceder al panel</p>
            </div>

            {{-- Tarjeta --}}
            <div id="login-card"
                 class="bg-slate-900 rounded-2xl border border-white/5 p-8 login-fade login-fade-3"
                 data-has-error="{{ $errors->isNotEmpty() ? 'true' : 'false' }}">

                {{-- Error de credenciales --}}
                @if ($errors->has('identificador'))
                    <div class="alert-error mb-6" role="alert">
                        <i class="fa-solid fa-triangle-exclamation w-4 h-4 mt-0.5 shrink-0"></i>
                        <span>{{ $errors->first('identificador') }}</span>
                    </div>
                @elseif (session('error'))
                    <div class="alert-error mb-6" role="alert">
                        <i class="fa-solid fa-triangle-exclamation w-4 h-4 mt-0.5 shrink-0"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @elseif (session('status'))
                    <div class="alert-success mb-6" role="alert">
                        <i class="fa-solid fa-circle-check w-4 h-4 mt-0.5 shrink-0"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Usuario o correo --}}
                    <div class="mb-5">
                        <label for="identificador"
                               class="block text-sm font-medium text-slate-300 mb-1.5">
                            Usuario o correo
                        </label>
                        <input type="text"
                               id="identificador"
                               name="identificador"
                               value="{{ old('identificador') }}"
                               required
                               autocomplete="username"
                               autofocus
                               placeholder="tu@correo.com o tu usuario"
                               class="field-input {{ $errors->has('identificador') ? 'has-error' : '' }}">
                        @error('identificador')
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
                                <i data-icon="show" class="fa-solid fa-eye"></i>
                                <i data-icon="hide" class="fa-solid fa-eye-slash hidden"></i>
                            </button>
                        </div>
                        @error('passwordUsuario')
                            <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Olvidé mi contraseña --}}
                    <div class="text-right mb-5 -mt-3">
                        <a href="{{ route('password.request') }}" class="text-xs text-slate-400 hover:text-blue-400 transition-colors">
                            ¿Olvidaste tu contraseña?
                        </a>
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
                            <i class="fa-solid fa-right-to-bracket"></i>
                            Iniciar sesión
                        </span>
                    </button>
                </form>

            </div>

            {{-- Volver --}}
            <div class="text-center mt-6 login-fade login-fade-4">
                <a href="{{ route('welcome') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-slate-500
                          hover:text-blue-400 transition-colors duration-200 group">
                    <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-0.5"></i>
                    Volver a la página principal
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
