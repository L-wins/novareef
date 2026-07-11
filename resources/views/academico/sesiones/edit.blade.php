@extends('layouts.app')

@section('titulo', 'Editar sesión académica')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('academico.sesiones.show', $sesion->idSesion) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a la sesión
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Editar sesión</h1>
            <p class="page-subheading">Solo se puede editar mientras la sesión no se haya abierto.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('academico.sesiones.update', $sesion->idSesion) }}" class="form-card" novalidate>
        @csrf
        @method('PUT')

        <div class="form-section">
            <p class="form-section-title">Tipo y tema</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="idTipoSesion">Tipo de sesión <span class="req">*</span></label>
                    <select id="idTipoSesion" name="idTipoSesion" data-nova-select class="form-select {{ $errors->has('idTipoSesion') ? 'is-invalid' : '' }}">
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->idTipoSesion }}" {{ (string) old('idTipoSesion', $sesion->idTipoSesion) === (string) $tipo->idTipoSesion ? 'selected' : '' }}>
                                {{ $tipo->etiqueta }}{{ $tipo->esOficial ? ' (Oficial FCF)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('idTipoSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="tema">Tema <span class="req">*</span></label>
                    <input type="text" id="tema" name="tema" value="{{ old('tema', $sesion->tema) }}" maxlength="150" class="form-input {{ $errors->has('tema') ? 'is-invalid' : '' }}">
                    @error('tema') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-textarea">{{ old('descripcion', $sesion->descripcion) }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Modalidad y lugar</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="modalidad">Modalidad <span class="req">*</span></label>
                    <select id="modalidad" name="modalidad" data-nova-select class="form-select">
                        <option value="presencial" {{ old('modalidad', $sesion->modalidad) === 'presencial' ? 'selected' : '' }}>Presencial</option>
                        <option value="virtual" {{ old('modalidad', $sesion->modalidad) === 'virtual' ? 'selected' : '' }}>Virtual</option>
                    </select>
                </div>
                <div class="form-group" id="wrap-url-virtual">
                    <label class="form-label" for="urlVirtual">URL de la sesión <span class="req">*</span></label>
                    <input type="text" id="urlVirtual" name="urlVirtual" value="{{ old('urlVirtual', $sesion->urlVirtual) }}" class="form-input {{ $errors->has('urlVirtual') ? 'is-invalid' : '' }}">
                    @error('urlVirtual') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="lugar">Lugar</label>
                    <input type="text" id="lugar" name="lugar" value="{{ old('lugar', $sesion->lugar) }}" maxlength="150" class="form-input">
                </div>
            </div>
        </div>

        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Fecha, hora y registro</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fechaSesion">Fecha <span class="req">*</span></label>
                    <input type="text" id="fechaSesion" name="fechaSesion" value="{{ old('fechaSesion', $sesion->fechaSesion->format('Y-m-d')) }}"
                           data-nova-date class="form-input {{ $errors->has('fechaSesion') ? 'is-invalid' : '' }}">
                    @error('fechaSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="horaSesion">Hora <span class="req">*</span></label>
                    {{-- Mismo patrón que la hora del partido: Flatpickr 24h, sin AM/PM --}}
                    <input type="text" id="horaSesion" name="horaSesion" value="{{ old('horaSesion', substr($sesion->horaSesion, 0, 5)) }}"
                           data-nova-date data-enable-time="true" data-no-calendar="true"
                           data-date-format="H:i" data-alt-format="H:i" placeholder="HH:MM"
                           class="form-input {{ $errors->has('horaSesion') ? 'is-invalid' : '' }}">
                    @error('horaSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="duracionMinutos">Duración (minutos) <span class="req">*</span></label>
                    <input type="number" id="duracionMinutos" name="duracionMinutos" value="{{ old('duracionMinutos', $sesion->duracionMinutos) }}" min="1" class="form-input {{ $errors->has('duracionMinutos') ? 'is-invalid' : '' }}">
                    @error('duracionMinutos') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="modoAsistencia">Modo de registro <span class="req">*</span></label>
                    <select id="modoAsistencia" name="modoAsistencia" data-nova-select class="form-select">
                        <option value="manual" {{ old('modoAsistencia', $sesion->modoAsistencia) === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="scanner" {{ old('modoAsistencia', $sesion->modoAsistencia) === 'scanner' ? 'selected' : '' }}>Scanner</option>
                    </select>
                </div>
                <div class="form-group span-2">
                    <label class="form-label form-label--check">
                        <input type="checkbox" name="esObligatoria" value="1" {{ old('esObligatoria', session()->hasOldInput() ? false : $sesion->esObligatoria) ? 'checked' : '' }}>
                        Esta sesión es de carácter obligatorio
                    </label>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <a href="{{ route('academico.sesiones.show', $sesion->idSesion) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Guardar cambios
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
