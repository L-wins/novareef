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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                         class="w-5 h-5 text-white">
                        <path fill-rule="evenodd"
                              d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z"
                              clip-rule="evenodd"/>
                    </svg>
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
                               class="field-input {{ $errors->has('nueva_password') ? 'has-error' : '' }}">
                        <button type="button" id="toggle-nueva"
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
                    @error('nueva_password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-slate-500">
                            Mínimo 8 caracteres. Usa letras y números para mayor seguridad.
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
                        <button type="button" id="toggle-confirmar"
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
                    @error('nueva_password_confirmation')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Botón principal --}}
                <button type="submit"
                        class="btn-login"
                        style="background:#3b82f6;box-shadow:0 4px 20px rgb(59 130 246/.25);">
                    <span class="btn-text flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd"
                                  d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z"
                                  clip-rule="evenodd"/>
                        </svg>
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

    <script>
    (function () {
        function initToggle(btnId, inputId) {
            var btn   = document.getElementById(btnId);
            var input = document.getElementById(inputId);
            if (!btn || !input) return;
            var show = btn.querySelector('[data-icon="show"]');
            var hide = btn.querySelector('[data-icon="hide"]');
            btn.addEventListener('click', function () {
                var isPwd = input.type === 'password';
                input.type = isPwd ? 'text' : 'password';
                show.classList.toggle('hidden', isPwd);
                hide.classList.toggle('hidden', !isPwd);
                btn.setAttribute('aria-label', isPwd ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            initToggle('toggle-nueva',     'nueva_password');
            initToggle('toggle-confirmar', 'nueva_password_confirmation');
        });
    }());
    </script>

@endsection
