@extends('layouts.auth')

@section('titulo', 'Restablecer contraseña')

@section('contenido')

    {{-- Círculos decorativos de fondo --}}
    <div class="fixed inset-0 pointer-events-none select-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full border border-blue-500/[0.06]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-blue-500/[0.04]"></div>
    </div>

    <div class="relative w-full max-w-md">

        {{-- Ícono de candado y título --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30"
                     style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
                    <i class="fa-solid fa-lock text-white text-base"></i>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">NovaReef</span>
            </div>
            <h1 class="text-2xl font-bold text-white">Elige tu nueva contraseña</h1>
            <p class="text-slate-400 mt-1 text-sm">
                Ingresa una contraseña nueva para tu cuenta.
            </p>
        </div>

        {{-- Tarjeta del formulario --}}
        <div id="login-card"
             class="bg-slate-900 rounded-2xl border border-white/5 shadow-2xl shadow-black/40 p-8"
             data-has-error="{{ $errors->isNotEmpty() ? 'true' : 'false' }}">

            @if ($errors->has('email'))
                <div class="alert-error mb-6" role="alert">
                    <i class="fa-solid fa-triangle-exclamation w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ $errors->first('email') }}</span>
                </div>
            @endif

            <form id="login-form" method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Correo --}}
                <div class="mb-5">
                    <label for="email"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Correo electrónico
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', $email) }}"
                           required
                           autocomplete="email"
                           class="field-input {{ $errors->has('email') ? 'has-error' : '' }}">
                </div>

                {{-- Nueva contraseña --}}
                <div class="mb-5">
                    <label for="password"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nueva contraseña
                    </label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="password"
                               name="password"
                               autocomplete="new-password"
                               autofocus
                               placeholder="Mínimo 8 caracteres"
                               data-password-strength
                               class="field-input {{ $errors->has('password') ? 'has-error' : '' }}">
                        <button type="button" data-password-toggle="password"
                                class="toggle-password" aria-label="Mostrar contraseña">
                            <i data-icon="show" class="fa-solid fa-eye"></i>
                            <i data-icon="hide" class="fa-solid fa-eye-slash hidden"></i>
                        </button>
                    </div>

                    {{-- Medidor de fortaleza (lo maneja login.js) --}}
                    <div class="strength-meter" data-strength-meter hidden aria-live="polite">
                        <div class="strength-meter__bars">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <p class="strength-meter__label" data-strength-label></p>
                    </div>

                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-slate-500">
                            Mínimo 8 caracteres. Combina mayúsculas, números y símbolos para una contraseña fuerte.
                        </p>
                    @enderror
                </div>

                {{-- Confirmar contraseña --}}
                <div class="mb-6">
                    <label for="password_confirmation"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Confirmar contraseña
                    </label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               autocomplete="new-password"
                               placeholder="Repite la contraseña"
                               class="field-input {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
                        <button type="button" data-password-toggle="password_confirmation"
                                class="toggle-password" aria-label="Mostrar contraseña">
                            <i data-icon="show" class="fa-solid fa-eye"></i>
                            <i data-icon="hide" class="fa-solid fa-eye-slash hidden"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Botón principal --}}
                <button type="submit" id="btn-login" class="btn-login">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        Restablecer contraseña
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
