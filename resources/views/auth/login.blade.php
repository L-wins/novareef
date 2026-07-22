@extends('layouts.auth')

@section('body_class', 'bg-slate-950 text-white antialiased min-h-screen flex')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="flex w-full min-h-screen">

    {{-- ═══ PANEL IZQUIERDO — BRANDING (claro, mismo formato que el login admin) ═══ --}}
    <div class="hidden lg:flex flex-1 bg-gradient-to-br from-white via-slate-50 to-slate-100 border-r border-slate-200
                flex-col items-center justify-center p-14 relative overflow-hidden">

        {{-- Decoración de fondo --}}
        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full"
                 style="background:radial-gradient(circle,rgba(79,142,247,0.14) 0%,transparent 65%);"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full"
                 style="background:radial-gradient(circle,rgba(56,189,248,0.10) 0%,transparent 65%);"></div>

            {{-- Círculos de cancha --}}
            <div class="login-circle" style="width:480px;height:480px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.6;"></div>
            <div class="login-circle" style="width:720px;height:720px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.4;"></div>

            {{-- Bandas referee sutiles --}}
            <div class="login-stripes"></div>

            {{-- Grid --}}
            <div class="absolute inset-0"
                 style="background-image:linear-gradient(rgba(15,23,42,0.035) 1px,transparent 1px),
                                        linear-gradient(90deg,rgba(15,23,42,0.035) 1px,transparent 1px);
                        background-size:48px 48px;"></div>
        </div>

        <div class="relative z-10 text-center max-w-sm">

            {{-- Logo --}}
            <div class="w-[76px] h-[76px] rounded-[22px] bg-slate-900 shadow-[0_0_50px_rgba(79,142,247,0.30)]
                        flex items-center justify-center mx-auto mb-7 login-fade login-fade-1">
                <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef" class="w-full h-full object-contain rounded-[22px]">
            </div>

            <h1 class="text-[2.75rem] font-black tracking-tight mb-2 leading-none login-fade login-fade-1">
                <span class="text-blue-600">Nova</span><span class="text-slate-900">Reef</span>
            </h1>

            <p class="text-slate-500 leading-relaxed mb-10 login-fade login-fade-2">
                Plataforma de gestión para colegios de árbitros de fútbol.
                Designaciones, torneos, finanzas y formación en un solo lugar.
            </p>

            {{-- Features --}}
            <div class="space-y-3.5 text-left login-fade login-fade-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-calendar-days text-blue-600 text-sm"></i>
                    </div>
                    <span class="text-slate-600 text-sm">Designaciones por partido y categoría</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-id-card text-sky-600 text-sm"></i>
                    </div>
                    <span class="text-slate-600 text-sm">Expedientes completos de árbitros</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-money-bill-trend-up text-indigo-600 text-sm"></i>
                    </div>
                    <span class="text-slate-600 text-sm">Control financiero del colegio</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20
                                flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-user-group text-purple-600 text-sm"></i>
                    </div>
                    <span class="text-slate-600 text-sm">Roles y permisos por función</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══ PANEL DERECHO — FORMULARIO (oscuro) ═══ --}}
    <div class="w-full lg:w-[460px] shrink-0 flex items-center justify-center p-8 lg:p-12 bg-slate-950 relative overflow-hidden">

        {{-- Gradiente sutil de fondo --}}
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-30"
                 style="background:radial-gradient(circle,rgba(79,142,247,0.08) 0%,transparent 65%);"></div>
        </div>

        <div class="w-full max-w-md relative">

            {{-- Logo móvil --}}
            <div class="flex lg:hidden items-center justify-center gap-3 mb-10 login-fade login-fade-1">
                <div class="w-10 h-10 rounded-xl overflow-hidden shadow-lg shadow-blue-500/30">
                    <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef" class="w-full h-full object-contain">
                </div>
                <span class="text-xl font-bold tracking-tight text-white">NovaReef</span>
            </div>

            <div class="login-fade login-fade-2">
                <h2 class="text-3xl font-bold text-white mb-1.5 tracking-tight">Bienvenido de nuevo</h2>
                <p class="text-slate-400 text-sm mb-8">Ingresa tus credenciales para acceder al panel</p>
            </div>

            {{-- Formulario — sin tarjeta, directo sobre el panel oscuro (mismo formato que el login admin) --}}
            <div id="login-card"
                 class="login-fade login-fade-3"
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
