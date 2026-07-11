@extends('layouts.app')

@section('titulo', 'Nueva sesión académica')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('academico.sesiones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a académico
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nueva sesión académica</h1>
            <p class="page-subheading">Programa una charla, capacitación o prueba oficial.</p>
        </div>
    </div>

    @if ($tipos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-triangle-exclamation" style="font-size:40px;"></i>
            <p>Tu colegio aún no tiene tipos de sesión configurados.</p>
            @can('editar-academico')
                <a href="{{ route('tipos-sesion-academica.index') }}" class="btn btn-primary" style="margin-top:1rem;">
                    Configurar tipos de sesión
                </a>
            @endcan
        </div>
    @else
    <form method="POST" action="{{ route('academico.sesiones.store') }}" class="form-card" novalidate>
        @csrf

        <div class="form-section">
            <p class="form-section-title">Tipo y tema</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="idTipoSesion">Tipo de sesión <span class="req">*</span></label>
                    <select id="idTipoSesion" name="idTipoSesion" data-nova-select data-placeholder="Selecciona un tipo"
                            class="form-select {{ $errors->has('idTipoSesion') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->idTipoSesion }}" {{ (string) old('idTipoSesion') === (string) $tipo->idTipoSesion ? 'selected' : '' }}>
                                {{ $tipo->etiqueta }}{{ $tipo->esOficial ? ' (Oficial FCF)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('idTipoSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="tema">Tema <span class="req">*</span></label>
                    <input type="text" id="tema" name="tema" value="{{ old('tema') }}" maxlength="150"
                           class="form-input {{ $errors->has('tema') ? 'is-invalid' : '' }}">
                    @error('tema') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-textarea">{{ old('descripcion') }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Modalidad y lugar</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="modalidad">Modalidad <span class="req">*</span></label>
                    <select id="modalidad" name="modalidad" data-nova-select class="form-select">
                        <option value="presencial" {{ old('modalidad', 'presencial') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                        <option value="virtual" {{ old('modalidad') === 'virtual' ? 'selected' : '' }}>Virtual</option>
                    </select>
                </div>
                <div class="form-group" id="wrap-url-virtual">
                    <label class="form-label" for="urlVirtual">URL de la sesión <span class="req">*</span></label>
                    <input type="text" id="urlVirtual" name="urlVirtual" value="{{ old('urlVirtual') }}"
                           placeholder="https://..." class="form-input {{ $errors->has('urlVirtual') ? 'is-invalid' : '' }}">
                    @error('urlVirtual') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group span-2">
                    <label class="form-label" for="lugar">Lugar</label>
                    <input type="text" id="lugar" name="lugar" value="{{ old('lugar') }}" maxlength="150"
                           placeholder="Ej. Auditorio del colegio" class="form-input">
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Fecha y hora</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fechaSesion">Fecha <span class="req">*</span></label>
                    <input type="text" id="fechaSesion" name="fechaSesion" value="{{ old('fechaSesion') }}"
                           data-nova-date placeholder="dd/mm/aaaa" class="form-input {{ $errors->has('fechaSesion') ? 'is-invalid' : '' }}">
                    @error('fechaSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="horaSesion">Hora <span class="req">*</span></label>
                    {{-- Mismo patrón que la hora del partido: Flatpickr 24h, sin AM/PM --}}
                    <input type="text" id="horaSesion" name="horaSesion" value="{{ old('horaSesion') }}"
                           data-nova-date data-enable-time="true" data-no-calendar="true"
                           data-date-format="H:i" data-alt-format="H:i" placeholder="HH:MM"
                           class="form-input {{ $errors->has('horaSesion') ? 'is-invalid' : '' }}">
                    @error('horaSesion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="duracionMinutos">Duración (minutos) <span class="req">*</span></label>
                    <input type="number" id="duracionMinutos" name="duracionMinutos" value="{{ old('duracionMinutos', 60) }}"
                           min="1" class="form-input {{ $errors->has('duracionMinutos') ? 'is-invalid' : '' }}">
                    @error('duracionMinutos') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Asistencia</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="dirigidaA">Dirigida a <span class="req">*</span></label>
                    <select id="dirigidaA" name="dirigidaA" data-nova-select class="form-select">
                        <option value="todos" {{ old('dirigidaA', 'todos') === 'todos' ? 'selected' : '' }}>Todos los árbitros</option>
                        <option value="categoria" {{ old('dirigidaA') === 'categoria' ? 'selected' : '' }}>Una categoría específica</option>
                    </select>
                </div>
                <div class="form-group" id="wrap-categoria">
                    <label class="form-label" for="idCategoria">Categoría <span class="req">*</span></label>
                    <select id="idCategoria" name="idCategoria" data-nova-select data-placeholder="Selecciona una categoría" class="form-select">
                        <option value="">— Selecciona —</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->idCategoria }}" {{ (string) old('idCategoria') === (string) $categoria->idCategoria ? 'selected' : '' }}>
                                {{ $categoria->nombreCategoria }}
                            </option>
                        @endforeach
                    </select>
                    @error('idCategoria') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="modoAsistencia">Modo de registro <span class="req">*</span></label>
                    <select id="modoAsistencia" name="modoAsistencia" data-nova-select class="form-select">
                        <option value="manual" {{ old('modoAsistencia', 'manual') === 'manual' ? 'selected' : '' }}>Manual (el árbitro marca desde su perfil)</option>
                        <option value="scanner" {{ old('modoAsistencia') === 'scanner' ? 'selected' : '' }}>Scanner (lectura de carné)</option>
                    </select>
                </div>
                <div class="form-group span-2">
                    <label class="form-label form-label--check">
                        <input type="checkbox" name="esObligatoria" value="1" {{ old('esObligatoria', session()->hasOldInput() ? false : true) ? 'checked' : '' }}>
                        Esta sesión es de carácter obligatorio
                    </label>
                    <p class="field-hint">Desmárcalo si es una sesión opcional (ej. un taller informativo).</p>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <a href="{{ route('academico.sesiones.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Crear sesión
            </button>
        </div>
    </form>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
