@extends('layouts.app')

@section('titulo', 'Editar cuenta admin')
@section('seccion', 'Configuración')

@push('scripts')
    @vite(['resources/js/configuracion/configuracion.js'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Editar cuenta admin</h1>
            <p class="page-subheading">{{ $cuenta->usernameUsuario ?? $cuenta->emailUsuario }}</p>
        </div>
        <a href="{{ route('configuracion.cuentas-admin.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('configuracion.cuentas-admin.update', $cuenta->idUsuario) }}">
        @csrf
        @method('PUT')

        <div class="detail-card" style="max-width:640px;">
            <div class="detail-card-header">
                <div class="detail-card-title">
                    <i class="fa-solid fa-user-shield" style="color:var(--accent);margin-right:0.5rem;"></i>
                    Datos de la cuenta
                </div>
            </div>
            <div class="detail-card-body">

                <div class="form-group">
                    <label class="form-label" for="nombreUsuario">Nombre completo</label>
                    <input type="text" name="nombreUsuario" id="nombreUsuario" class="form-input"
                           value="{{ old('nombreUsuario', $cuenta->nombreUsuario) }}" maxlength="150" required>
                    @error('nombreUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="usernameUsuario">Nombre de usuario</label>
                    <input type="text" name="usernameUsuario" id="usernameUsuario" class="form-input"
                           value="{{ old('usernameUsuario', $cuenta->usernameUsuario) }}"
                           maxlength="60" required autocomplete="off"
                           data-username-check
                           data-endpoint="{{ route('configuracion.cuentas-admin.verificar-username') }}"
                           data-ignorar="{{ $cuenta->idUsuario }}">
                    <span class="username-check" data-username-status aria-live="polite"></span>
                    @error('usernameUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="emailUsuario">Correo electrónico (opcional)</label>
                    <input type="email" name="emailUsuario" id="emailUsuario" class="form-input"
                           value="{{ old('emailUsuario', $cuenta->emailUsuario) }}" maxlength="255">
                    @error('emailUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="rolUsuario">Rol</label>
                    <select name="rolUsuario" id="rolUsuario" class="form-select" required>
                        @foreach ([
                            'ejecutivo'  => 'Ejecutivo',
                            'tesorero'   => 'Tesorero',
                            'designador' => 'Designador',
                            'sanciones'  => 'Sanciones',
                            'tecnico'    => 'Técnico',
                            'veedor'     => 'Veedor',
                        ] as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('rolUsuario', $cuenta->rolUsuario) === $valor ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    @error('rolUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>
        </div>

        <div style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar cambios
            </button>
        </div>

    </form>

</div>
@endsection
