@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')

@section('contenido')

    {{-- Círculos decorativos de fondo --}}
    <div class="fixed inset-0 pointer-events-none select-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-blue-500/[0.06]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-blue-500/[0.04]"></div>
    </div>

    <div class="relative w-full max-w-md">

        {{-- Ícono y título --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30"
                     style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
                    <i class="fa-solid fa-key text-white text-base"></i>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">NovaReef</span>
            </div>
            <h1 class="text-2xl font-bold text-white">Recupera tu contraseña</h1>
            <p class="text-slate-400 mt-1 text-sm">
                Ingresa tu correo y te enviaremos un enlace para restablecerla.
            </p>
        </div>

        {{-- Tarjeta del formulario --}}
        <div id="login-card"
             class="bg-slate-900 rounded-2xl border border-white/5 shadow-2xl shadow-black/40 p-8"
             data-has-error="{{ $errors->isNotEmpty() ? 'true' : 'false' }}">

            @if (session('status'))
                <div class="alert-success mb-6" role="alert">
                    <i class="fa-solid fa-circle-check w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @elseif ($errors->has('email'))
                <div class="alert-error mb-6" role="alert">
                    <i class="fa-solid fa-triangle-exclamation w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ $errors->first('email') }}</span>
                </div>
            @endif

            <form id="login-form" method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf

                {{-- Correo --}}
                <div class="mb-6">
                    <label for="email"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Correo electrónico
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email"
                           autofocus
                           placeholder="tu@correo.com"
                           class="field-input {{ $errors->has('email') ? 'has-error' : '' }}">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" id="btn-login" class="btn-login">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar enlace
                    </span>
                </button>
            </form>

        </div>

        {{-- Volver --}}
        <div class="text-center mt-6">
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500
                      hover:text-blue-400 transition-colors duration-200 group">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-0.5"></i>
                Volver a iniciar sesión
            </a>
        </div>

    </div>

@endsection
