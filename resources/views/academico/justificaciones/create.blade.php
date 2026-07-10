@extends('layouts.app')

@section('titulo', 'Justificar inasistencia')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('academico.mis-clases') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a mis clases
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Justificar inasistencia</h1>
            <p class="page-subheading">
                {{ $asistencia->sesion->tema }} — {{ $asistencia->sesion->fechaSesion->format('d/m/Y') }}
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">Revisa los campos marcados abajo.</div>
    @endif

    <p class="field-hint" style="margin-bottom:1rem;">
        Tienes hasta el {{ $asistencia->sesion->fechaLimiteJustificacion->format('d/m/Y') }} para enviar esta justificación.
    </p>

    <form method="POST" action="{{ route('academico.justificaciones.store', $asistencia->idAsistencia) }}" class="form-card" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="form-section" style="border-bottom:none;">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="motivo">Motivo <span class="req">*</span></label>
                    <textarea id="motivo" name="motivo" class="form-textarea {{ $errors->has('motivo') ? 'is-invalid' : '' }}">{{ old('motivo') }}</textarea>
                    @error('motivo') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="documentoPdf">Documento de soporte (PDF, opcional)</label>
                    <input type="file" id="documentoPdf" name="documentoPdf" accept="application/pdf" class="form-input">
                    <p id="documento-pdf-nombre" class="field-hint">Ningún archivo seleccionado</p>
                    @error('documentoPdf') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <a href="{{ route('academico.mis-clases') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i>
                Enviar justificación
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
