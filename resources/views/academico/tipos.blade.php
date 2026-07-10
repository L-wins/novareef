@extends('layouts.app')

@section('titulo', 'Tipos de sesión')
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
            <h1 class="page-heading">Tipos de sesión</h1>
            <p class="page-subheading">Define el catálogo de tipos de sesión académica de tu colegio.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div class="form-card">
        <div class="form-section" style="padding-bottom:0;">
            <p class="form-section-title">Tipos registrados</p>
        </div>

        @if ($tipos->isEmpty())
            <div style="padding:1.5rem;text-align:center;color:var(--aca-text-mute);font-size:0.875rem;">
                No hay tipos de sesión registrados aún.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Etiqueta</th>
                            <th>Oficial FCF</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tipos as $tipo)
                        <tr>
                            <td class="td-primary">{{ $tipo->nombre }}</td>
                            <td>{{ $tipo->etiqueta }}</td>
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
                                <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
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

        <div class="form-section" style="background:rgba(79,142,247,.03);border-bottom:none;margin-top:1rem;">
            <p class="form-section-title">Agregar tipo de sesión</p>
            <form method="POST" action="{{ route('tipos-sesion-academica.store') }}" novalidate>
                @csrf
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre interno <span class="req">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" maxlength="60"
                               placeholder="Ej. prueba_oficial_fcf" class="form-input {{ $errors->has('nombre') ? 'is-invalid' : '' }}">
                        @error('nombre') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Etiqueta visible <span class="req">*</span></label>
                        <input type="text" name="etiqueta" value="{{ old('etiqueta') }}" maxlength="80"
                               placeholder="Ej. Prueba oficial FCF" class="form-input {{ $errors->has('etiqueta') ? 'is-invalid' : '' }}">
                        @error('etiqueta') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label" style="flex-direction:row;align-items:center;gap:0.5rem;">
                            <input type="checkbox" name="esOficial" value="1" {{ old('esOficial') ? 'checked' : '' }}>
                            Es una sesión oficial (ej. prueba oficial FCF)
                        </label>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-textarea">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
