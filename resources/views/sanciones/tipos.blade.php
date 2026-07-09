@extends('layouts.app')

@section('titulo', 'Tipos de sanción')
@section('seccion', 'Sanciones')

@push('styles')
    @vite(['resources/css/sanciones/sanciones.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('sanciones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a sanciones
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Tipos de sanción</h1>
            <p class="page-subheading">Define el catálogo de faltas disciplinarias de tu colegio.</p>
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
            <div style="padding:1.5rem;text-align:center;color:var(--san-text-mute);font-size:0.875rem;">
                No hay tipos de sanción registrados aún.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Etiqueta</th>
                            <th>Severidad</th>
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
                                <span class="badge {{ match($tipo->severidad) { 'grave' => 'badge-red', 'moderada' => 'badge-amber', default => 'badge-gray' } }}">
                                    {{ ucfirst($tipo->severidad) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $tipo->esActivo ? 'badge-green' : 'badge-gray' }}">
                                    {{ $tipo->esActivo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                                    <form method="POST" action="{{ route('tipos-sancion.toggleActivo', $tipo->idTipoSancion) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            {{ $tipo->esActivo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('tipos-sancion.destroy', $tipo->idTipoSancion) }}"
                                          data-confirm-submit
                                          data-confirm-title="¿Eliminar tipo de sanción?"
                                          data-confirm-text="Solo se puede eliminar si no tiene sanciones registradas.">
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
            <p class="form-section-title">Agregar tipo de sanción</p>
            <form method="POST" action="{{ route('tipos-sancion.store') }}" novalidate>
                @csrf
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre interno <span class="req">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" maxlength="60"
                               placeholder="Ej. inasistencia_injustificada" class="form-input {{ $errors->has('nombre') ? 'is-invalid' : '' }}">
                        @error('nombre') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Etiqueta visible <span class="req">*</span></label>
                        <input type="text" name="etiqueta" value="{{ old('etiqueta') }}" maxlength="80"
                               placeholder="Ej. Inasistencia injustificada" class="form-input {{ $errors->has('etiqueta') ? 'is-invalid' : '' }}">
                        @error('etiqueta') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Severidad <span class="req">*</span></label>
                        <select name="severidad" data-nova-select class="form-select {{ $errors->has('severidad') ? 'is-invalid' : '' }}">
                            <option value="leve">Leve</option>
                            <option value="moderada">Moderada</option>
                            <option value="grave">Grave</option>
                        </select>
                        @error('severidad') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Días de suspensión sugeridos</label>
                        <input type="number" name="diasSuspensionSugeridos" value="{{ old('diasSuspensionSugeridos') }}" min="0" class="form-input">
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

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
