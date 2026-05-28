@extends('layouts.app')

@section('titulo', 'Nuevo árbitro')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
        </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                         style="width:16px;height:16px;flex-shrink:0;margin-top:2px;color:#6ee7b7;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd"/>
                    </svg>
                    <p style="font-size:0.85rem;color:#6ee7b7;margin:0;line-height:1.5;">
                        Los demás datos del perfil (peso, estatura, dirección, vehículo, etc.) serán completados
                        por el árbitro en su primer inicio de sesión.
                    </p>
                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('arbitros.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;">
                        <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM2.046 15.253c-.332 1.295.997 2.267 2.164 1.799 1.023-.409 2.56-.82 4.29-.82 1.73 0 3.267.411 4.29.82 1.167.468 2.496-.504 2.164-1.799A6.97 6.97 0 0 0 8.5 11a6.97 6.97 0 0 0-6.454 4.253Z"/>
                        <path d="M12.5 5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM15.5 4a.75.75 0 0 1 .75.75v1h1a.75.75 0 0 1 0 1.5h-1v1a.75.75 0 0 1-1.5 0v-1h-1a.75.75 0 0 1 0-1.5h1v-1A.75.75 0 0 1 15.5 4Z"/>
                    </svg>
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
