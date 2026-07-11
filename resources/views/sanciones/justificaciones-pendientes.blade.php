@extends('layouts.app')

@section('titulo', 'Justificaciones pendientes')
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
            <h1 class="page-heading">Justificaciones pendientes</h1>
            <p class="page-subheading">Revisa las justificaciones de inasistencia académica enviadas por los árbitros.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    @if ($justificaciones->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check" style="font-size:48px;"></i>
            <p>No hay justificaciones pendientes de revisión.</p>
        </div>
    @else
        @foreach ($justificaciones as $justificacion)
            <div class="form-card" style="margin-bottom:1rem;">
                <div class="form-section" style="border-bottom:none;">
                    <div class="page-header" style="margin-bottom:0.75rem;">
                        <div class="page-header-left">
                            <span class="td-primary">{{ $justificacion->arbitro->usuario->nombreUsuario ?? '—' }}</span>
                            <span class="td-secondary">
                                {{ $justificacion->asistencia->sesion->tema ?? '—' }} —
                                {{ $justificacion->asistencia->sesion->fechaSesion?->format('d/m/Y') }}
                            </span>
                        </div>
                        <span class="badge badge-amber">Vence {{ $justificacion->fechaLimite->format('d/m/Y') }}</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Motivo</label>
                            <span>{{ $justificacion->motivo }}</span>
                        </div>
                        @if ($justificacion->documentoPdf)
                            <div class="form-group">
                                <a href="{{ route('sanciones.justificaciones.documento', $justificacion->idJustificacion) }}" class="btn btn-secondary btn-sm" style="width:fit-content;">
                                    <i class="fa-solid fa-file-pdf"></i> Ver documento adjunto
                                </a>
                            </div>
                        @endif
                    </div>

                    <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                        <form method="POST" action="{{ route('sanciones.justificaciones.revisar', $justificacion->idJustificacion) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="accion" value="aprobar">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-check"></i> Aprobar
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" data-abrir-rechazo="{{ $justificacion->idJustificacion }}">
                            <i class="fa-solid fa-xmark"></i> Rechazar
                        </button>
                    </div>

                    <form method="POST" action="{{ route('sanciones.justificaciones.revisar', $justificacion->idJustificacion) }}"
                          id="form-rechazo-{{ $justificacion->idJustificacion }}" style="display:none;margin-top:0.75rem;">
                        @csrf @method('PUT')
                        <input type="hidden" name="accion" value="rechazar">
                        <div class="form-group">
                            <label class="form-label">Motivo de rechazo <span class="req">*</span></label>
                            <textarea name="motivoRechazo" class="form-textarea"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">Confirmar rechazo</button>
                    </form>
                </div>
            </div>
        @endforeach

        @if ($justificaciones->hasPages())
            <div class="pagination-wrapper">{{ $justificaciones->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
