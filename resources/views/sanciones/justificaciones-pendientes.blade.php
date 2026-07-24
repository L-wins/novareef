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
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    @if ($justificaciones->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check just-empty-icon" style="font-size:48px;"></i>
            <p>No hay justificaciones pendientes de revisión.</p>
        </div>
    @else
        <div class="just-list">
            @foreach ($justificaciones as $justificacion)
                @php
                    $diasParaVencer = now()->startOfDay()->diffInDays($justificacion->fechaLimite->startOfDay(), false);
                    $urgencia = $diasParaVencer <= 0 ? 'critica' : ($diasParaVencer <= 1 ? 'media' : 'normal');
                @endphp
                <div class="just-card">
                    <div class="just-card__head">
                        <div>
                            <div class="just-card__arbitro">{{ $justificacion->arbitro->usuario->nombreUsuario ?? '—' }}</div>
                            <div class="just-card__sesion">
                                {{ $justificacion->asistencia->sesion->tema ?? '—' }} —
                                {{ $justificacion->asistencia->sesion->fechaSesion?->format('d/m/Y') }}
                            </div>
                        </div>
                        <span class="just-vence" data-urgencia="{{ $urgencia }}">
                            <i class="fa-solid fa-clock"></i>
                            Vence {{ $justificacion->fechaLimite->format('d/m/Y') }}
                        </span>
                    </div>

                    <p class="just-card__motivo">{{ $justificacion->motivo }}</p>

                    @if ($justificacion->documentoPdf)
                        <div class="just-card__doc">
                            <a href="{{ route('sanciones.justificaciones.documento', $justificacion->idJustificacion) }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file-pdf"></i> Ver documento adjunto
                            </a>
                        </div>
                    @endif

                    <div class="just-actions">
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

                    <div class="just-rechazo" id="wrap-rechazo-{{ $justificacion->idJustificacion }}">
                        <div class="just-rechazo__inner">
                            <form method="POST" action="{{ route('sanciones.justificaciones.revisar', $justificacion->idJustificacion) }}"
                                  id="form-rechazo-{{ $justificacion->idJustificacion }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="accion" value="rechazar">
                                <div class="form-group">
                                    <label class="form-label">Motivo de rechazo <span class="req">*</span></label>
                                    <textarea name="motivoRechazo" class="form-textarea" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm">Confirmar rechazo</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($justificaciones->hasPages())
            <div class="pagination-wrapper">{{ $justificaciones->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
