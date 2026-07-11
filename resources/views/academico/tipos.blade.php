@extends('layouts.app')

@section('titulo', 'Tipos de sesión')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@php
    // El formulario arranca plegado; si el envío anterior falló la validación
    // se muestra expandido para no esconder los errores.
    $formAbierto = $errors->any() || old('etiqueta') !== null;
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('academico.sesiones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a académico
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Tipos de sesión</h1>
            <p class="page-subheading">Define el catálogo de tipos de sesión académica de tu colegio.</p>
        </div>
        <button type="button" class="btn btn-primary" data-toggle-panel="panel-nuevo-tipo" data-focus="input-etiqueta">
            <i class="fa-solid fa-plus"></i>
            Agregar tipo
        </button>
    </div>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    {{-- Formulario de alta — plegado hasta pulsar "Agregar tipo" --}}
    <div class="form-card panel-plegable {{ $formAbierto ? '' : 'is-oculto' }}" id="panel-nuevo-tipo">
        <div class="form-section">
            <p class="form-section-title">Nuevo tipo de sesión</p>
            <form method="POST" action="{{ route('tipos-sesion-academica.store') }}" novalidate>
                @csrf
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="input-etiqueta">Nombre <span class="req">*</span></label>
                        <input type="text" id="input-etiqueta" name="etiqueta" value="{{ old('etiqueta') }}" maxlength="80"
                               placeholder="Ej. Prueba oficial FCF" class="form-input {{ $errors->has('etiqueta') ? 'is-invalid' : '' }}">
                        @error('etiqueta') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group form-group--centrada">
                        <label class="form-label form-label--check">
                            <input type="checkbox" name="esOficial" value="1" {{ old('esOficial') ? 'checked' : '' }}>
                            Es una sesión oficial (ej. prueba oficial FCF)
                        </label>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-textarea">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div class="form-actions-fila">
                    <button type="button" class="btn btn-secondary" data-toggle-panel="panel-nuevo-tipo">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Catálogo --}}
    <div class="form-card">
        <div class="form-section form-section--titulo">
            <p class="form-section-title">Tipos registrados</p>
        </div>

        @if ($tipos->isEmpty())
            <div class="card-empty-note">
                No hay tipos de sesión registrados aún. Crea el primero con el botón «Agregar tipo».
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Oficial FCF</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tipos as $tipo)
                        <tr>
                            <td class="td-primary">{{ $tipo->etiqueta }}</td>
                            <td class="td-descripcion">{{ $tipo->descripcion ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $tipo->esOficial ? 'badge-blue' : 'badge-gray' }}">
                                    {{ $tipo->esOficial ? 'Oficial' : 'No oficial' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $tipo->esActivo ? 'badge-green' : 'badge-gray' }}">
                                    {{ $tipo->esActivo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="acciones-fila">
                                    <form method="POST" action="{{ route('tipos-sesion-academica.toggleActivo', $tipo->idTipoSesion) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            {{ $tipo->esActivo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('tipos-sesion-academica.destroy', $tipo->idTipoSesion) }}"
                                          data-confirm-submit
                                          data-confirm-title="¿Eliminar tipo de sesión?"
                                          data-confirm-text="Solo se puede eliminar si no tiene sesiones registradas.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
