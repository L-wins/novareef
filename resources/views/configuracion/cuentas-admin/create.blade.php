@extends('layouts.app')

@section('titulo', 'Nueva cuenta admin')
@section('seccion', 'Configuración')

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nueva cuenta admin</h1>
            <p class="page-subheading">Crea un usuario interno del colegio con acceso administrativo</p>
        </div>
        <a href="{{ route('configuracion.cuentas-admin.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('configuracion.cuentas-admin.store') }}">
        @csrf

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
                           value="{{ old('nombreUsuario') }}" maxlength="150" required>
                    @error('nombreUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="usernameUsuario">Nombre de usuario (para iniciar sesión)</label>
                    <input type="text" name="usernameUsuario" id="usernameUsuario" class="form-input"
                           value="{{ old('usernameUsuario') }}" maxlength="60" required>
                    @error('usernameUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="emailUsuario">Correo electrónico (opcional)</label>
                    <input type="email" name="emailUsuario" id="emailUsuario" class="form-input"
                           value="{{ old('emailUsuario') }}" maxlength="255">
                    @error('emailUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label" for="rolUsuario">Rol</label>
                    <select name="rolUsuario" id="rolUsuario" class="form-select" required>
                        <option value="">Selecciona un rol</option>
                        @foreach ([
                            'ejecutivo'  => 'Ejecutivo',
                            'tesorero'   => 'Tesorero',
                            'designador' => 'Designador',
                            'sanciones'  => 'Sanciones',
                            'tecnico'    => 'Técnico',
                            'veedor'     => 'Veedor',
                        ] as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('rolUsuario') === $valor ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    @error('rolUsuario')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-note form-note--info" style="margin-top:1rem;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Las credenciales (usuario + contraseña temporal) se enviarán al correo del colegio.
                        La cuenta deberá cambiar la contraseña en su primer acceso.
                    </span>
                </div>

            </div>
        </div>

        <div style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Crear cuenta
            </button>
        </div>

    </form>

</div>
@endsection
