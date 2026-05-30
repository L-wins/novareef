@extends('layouts.app')

@section('titulo', 'Nuevo árbitro')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a árbitros
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nuevo árbitro</h1>
            <p class="page-subheading">El código de carné y la contraseña temporal se generan automáticamente.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('arbitros.store') }}" novalidate id="arbitro-form">
        @csrf

        <div class="form-card">

            {{-- Sección 1: Cuenta de acceso --}}
            <div class="form-section">
                <p class="form-section-title">Cuenta de acceso</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group span-2">
                        <label for="nombreUsuario" class="form-label">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="nombreUsuario" name="nombreUsuario"
                               value="{{ old('nombreUsuario') }}" maxlength="150"
                               placeholder="Ej. Juan Carlos Pérez"
                               class="form-input {{ $errors->has('nombreUsuario') ? 'is-invalid' : '' }}" autofocus>
                        @error('nombreUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="emailUsuario" class="form-label">Correo electrónico <span class="req">*</span></label>
                        <input type="email" id="emailUsuario" name="emailUsuario"
                               value="{{ old('emailUsuario') }}" placeholder="arbitro@ejemplo.com"
                               class="form-input {{ $errors->has('emailUsuario') ? 'is-invalid' : '' }}">
                        @error('emailUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefonoUsuario" class="form-label">Teléfono <span class="req">*</span></label>
                        <input type="text" id="telefonoUsuario" name="telefonoUsuario"
                               value="{{ old('telefonoUsuario') }}" maxlength="20"
                               placeholder="Ej. 3001234567"
                               class="form-input {{ $errors->has('telefonoUsuario') ? 'is-invalid' : '' }}">
                        @error('telefonoUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 2: Identificación --}}
            <div class="form-section">
                <p class="form-section-title">Identificación</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="tipoDocumento" class="form-label">Tipo de documento <span class="req">*</span></label>
                        <select id="tipoDocumento" name="tipoDocumento"
                                class="form-select {{ $errors->has('tipoDocumento') ? 'is-invalid' : '' }}">
                            @php $tipo = old('tipoDocumento', 'cedula'); @endphp
                            <option value="cedula"      {{ $tipo === 'cedula'      ? 'selected' : '' }}>Cédula</option>
                            <option value="pasaporte"   {{ $tipo === 'pasaporte'   ? 'selected' : '' }}>Pasaporte</option>
                            <option value="extranjeria" {{ $tipo === 'extranjeria' ? 'selected' : '' }}>Extranjería</option>
                        </select>
                        @error('tipoDocumento') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="numeroDocumento" class="form-label">Número de documento <span class="req">*</span></label>
                        <input type="text" id="numeroDocumento" name="numeroDocumento"
                               value="{{ old('numeroDocumento') }}" maxlength="30"
                               placeholder="Ej. 1234567890"
                               class="form-input {{ $errors->has('numeroDocumento') ? 'is-invalid' : '' }}">
                        @error('numeroDocumento') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="lugarExpedicionCC" class="form-label">Lugar de expedición</label>
                        <input type="text" id="lugarExpedicionCC" name="lugarExpedicionCC"
                               value="{{ old('lugarExpedicionCC') }}" maxlength="100"
                               placeholder="Ej. Bogotá (opcional — puede completarlo el árbitro)"
                               class="form-input {{ $errors->has('lugarExpedicionCC') ? 'is-invalid' : '' }}">
                        @error('lugarExpedicionCC') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 3: Datos del colegio --}}
            <div class="form-section">
                <p class="form-section-title">Datos del colegio</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="idCategoria" class="form-label">Categoría <span class="req">*</span></label>
                        <select id="idCategoria" name="idCategoria"
                                class="form-select {{ $errors->has('idCategoria') ? 'is-invalid' : '' }}">
                            <option value="">Selecciona una categoría</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->idCategoria }}"
                                    {{ old('idCategoria') == $categoria->idCategoria ? 'selected' : '' }}>
                                    {{ $categoria->nombreCategoria }}
                                </option>
                            @endforeach
                        </select>
                        @error('idCategoria') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="fechaIngresoColegio" class="form-label">Fecha de ingreso <span class="req">*</span></label>
                        <input type="date" id="fechaIngresoColegio" name="fechaIngresoColegio"
                               value="{{ old('fechaIngresoColegio') }}"
                               class="form-input {{ $errors->has('fechaIngresoColegio') ? 'is-invalid' : '' }}">
                        @error('fechaIngresoColegio') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Nota informativa --}}
            <div class="form-section" style="background:rgba(16,185,129,.05);border-bottom:none;">
                <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                    <i class="fa-solid fa-circle-info" style="font-size:16px;flex-shrink:0;margin-top:2px;color:#6ee7b7;"></i>
                    <p style="font-size:0.85rem;color:#6ee7b7;margin:0;line-height:1.5;">
                        Los demás datos del perfil (peso, estatura, dirección, vehículo, etc.) serán completados
                        por el árbitro en su primer inicio de sesión.
                    </p>
                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('arbitros.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i>
                    Registrar árbitro
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
