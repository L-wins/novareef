@extends('layouts.auth')

@section('titulo', 'Cambiar contraseña')

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
            <h1 class="text-2xl font-bold text-white">Cambia tu contraseña</h1>
            <p class="text-slate-400 mt-1 text-sm">
                Por seguridad debes elegir una contraseña nueva antes de continuar.
            </p>
        </div>

        {{-- Tarjeta del formulario --}}
        <div class="bg-slate-900 rounded-2xl border border-white/5 shadow-2xl shadow-black/40 p-8">

            <form method="POST" action="{{ route('password.change.update') }}" novalidate>
                @csrf

                {{-- Nueva contraseña --}}
                <div class="mb-5">
                    <label for="nueva_password"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nueva contraseña
                    </label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="nueva_password"
                               name="nueva_password"
                               autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres"
                               data-password-strength
                               class="field-input {{ $errors->has('nueva_password') ? 'has-error' : '' }}">
                        <button type="button" data-password-toggle="nueva_password"
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

                    @error('nueva_password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-slate-500">
                            Mínimo 8 caracteres. Combina mayúsculas, números y símbolos para una contraseña fuerte.
                        </p>
                    @enderror
                </div>

                {{-- Confirmar contraseña --}}
                <div class="mb-6">
                    <label for="nueva_password_confirmation"
                           class="block text-sm font-medium text-slate-300 mb-1.5">
                        Confirmar contraseña
                    </label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="nueva_password_confirmation"
                               name="nueva_password_confirmation"
                               autocomplete="new-password"
                               placeholder="Repite la contraseña"
                               class="field-input {{ $errors->has('nueva_password_confirmation') ? 'has-error' : '' }}">
                        <button type="button" data-password-toggle="nueva_password_confirmation"
                                class="toggle-password" aria-label="Mostrar contraseña">
                            <i data-icon="show" class="fa-solid fa-eye"></i>
                            <i data-icon="hide" class="fa-solid fa-eye-slash hidden"></i>
                        </button>
                    </div>
                    @error('nueva_password_confirmation')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Botón principal --}}
                <button type="submit"
                        class="btn-login"
                        data-strength-submit
                        style="background:#3b82f6;box-shadow:0 4px 20px rgb(59 130 246/.25);">
                    <span class="btn-text flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        Cambiar contraseña
                    </span>
                </button>

            </form>

        </div>

        {{-- Cerrar sesión --}}
        <div class="text-center mt-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="text-sm text-slate-500 hover:text-slate-300 transition-colors duration-200 underline">
                    Cerrar sesión
                </button>
            </form>
        </div>

    </div>

@endsection
