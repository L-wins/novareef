@extends('layouts.app')

@section('titulo', 'Mis Clases')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@php
    $etiquetasAsistencia = [
        'presente'                 => ['Presente', 'green'],
        'ausente'                  => ['Ausente', 'red'],
        'justificacion_pendiente'  => ['Justificación pendiente', 'amber'],
        'justificado'              => ['Justificado', 'blue'],
        'justificacion_rechazada'  => ['Justificación rechazada', 'gray'],
    ];
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Mis Clases</h1>
            <p class="page-subheading">Tus sesiones académicas, historial y porcentaje de asistencia.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div class="aca-stat-row">
        <div class="aca-stat">
            <div class="aca-stat__label">Asistencia general</div>
            <div class="aca-stat__value">{{ $porcentajeAsistencia !== null ? $porcentajeAsistencia . '%' : '—' }}</div>
        </div>
        <div class="aca-stat">
            <div class="aca-stat__label">Próximas sesiones</div>
            <div class="aca-stat__value">{{ $proximas->count() }}</div>
        </div>
    </div>

    <p class="aca-section-title">Próximas</p>
    @if ($proximas->isEmpty())
        <div class="empty-state">
            <i class="fa-regular fa-calendar" style="font-size:40px;"></i>
            <p>No tienes sesiones próximas.</p>
        </div>
    @else
        @foreach ($proximas as $sesion)
            @php $asistencia = $sesion->asistencias->first(); @endphp
            <div class="aca-proxima-item">
                <div>
                    <div class="td-primary">{{ $sesion->tema }}</div>
                    <div class="td-secondary">
                        {{ $sesion->tipo->etiqueta ?? '—' }} ·
                        {{ $sesion->fechaSesion->format('d/m/Y') }} ·
                        {{ \Illuminate\Support\Carbon::parse($sesion->horaSesion)->format('H:i') }}
                        @if ($sesion->esOficial) · <span class="badge badge-blue">Oficial FCF</span> @endif
                    </div>
                </div>
                @if ($sesion->sesionAbierta && $sesion->modoAsistencia === 'manual' && $asistencia?->estadoAsistencia !== 'presente')
                    <form method="POST" action="{{ route('academico.asistencias.marcar', $asistencia->idAsistencia) }}"
                          data-confirm-submit
                          data-confirm-title="¿Marcar asistencia?"
                          data-confirm-text="Se registrará tu presencia en esta sesión.">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-check"></i> Marcar asistencia
                        </button>
                    </form>
                @elseif ($asistencia?->estadoAsistencia === 'presente')
                    <span class="badge badge-green">Presente</span>
                @endif
            </div>
        @endforeach
    @endif

    <p class="aca-section-title" style="margin-top:2rem;">Historial</p>
    @if ($historial->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-clock-rotate-left" style="font-size:40px;"></i>
            <p>Aún no tienes sesiones finalizadas.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sesión</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historial as $sesion)
                        @php
                            $asistencia = $sesion->asistencias->first();
                            [$aLabel, $aColor] = $etiquetasAsistencia[$asistencia?->estadoAsistencia] ?? ['—', 'gray'];
                            $puedeJustificar = $asistencia
                                && $asistencia->estadoAsistencia === 'ausente'
                                && ! $asistencia->justificacion
                                && now()->lte($sesion->fechaLimiteJustificacion->endOfDay());
                        @endphp
                        <tr>
                            <td>
                                <span class="td-primary">{{ $sesion->tema }}</span>
                                <span class="td-secondary">{{ $sesion->tipo->etiqueta ?? '—' }}</span>
                            </td>
                            <td>{{ $sesion->fechaSesion->format('d/m/Y') }}</td>
                            <td><span class="badge badge-{{ $aColor }}">{{ $aLabel }}</span></td>
                            <td class="text-right">
                                @if ($puedeJustificar)
                                    <a href="{{ route('academico.justificaciones.create', $asistencia->idAsistencia) }}" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-file-circle-question"></i> Justificar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($historial->hasPages())
            <div class="pagination-wrapper">{{ $historial->links() }}</div>
        @endif
    @endif

</div>
@endsection
