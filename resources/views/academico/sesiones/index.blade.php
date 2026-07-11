@extends('layouts.app')

@section('titulo', 'Académico')
@section('seccion', 'Académico')

@push('styles')
    @vite(['resources/css/academico/academico.css'])
@endpush

@php
    $etiquetasEstado = [
        'programada' => ['Programada', 'gray'],
        'en_curso'   => ['En curso', 'amber'],
        'finalizada' => ['Finalizada', 'green'],
        'cancelada'  => ['Cancelada', 'red'],
    ];
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Sesiones académicas</h1>
            <p class="page-subheading">Charlas, capacitaciones y pruebas oficiales del colegio.</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            @can('editar-academico')
                <a href="{{ route('tipos-sesion-academica.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-list-check"></i>
                    Tipos de sesión
                </a>
            @endcan
            <a href="{{ route('sanciones.justificaciones.pendientes') }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-circle-question"></i>
                Justificaciones
            </a>
            <a href="{{ route('academico.sesiones.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nueva sesión
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    @if ($sesiones->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-graduation-cap" style="font-size:48px;"></i>
            <p>Aún no hay sesiones académicas registradas.</p>
        </div>
    @else
        <div class="sesiones-grid">
            @foreach ($sesiones as $sesion)
                @php
                    [$estadoLabel, $estadoColor] = $etiquetasEstado[$sesion->estadoSesion] ?? ['—', 'gray'];
                    $esperados = $sesion->asistencias_count ?: 1;
                    $pct = round(($sesion->presentes_count / $esperados) * 100);
                @endphp
                <a href="{{ route('academico.sesiones.show', $sesion->idSesion) }}" class="sesion-card">
                    <div class="sesion-card__header">
                        <span class="sesion-card__tema">{{ $sesion->tema }}</span>
                        <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                    </div>
                    <div class="sesion-card__meta">
                        <span><i class="fa-solid fa-tag"></i>{{ $sesion->tipo->etiqueta ?? '—' }}</span>
                        @if ($sesion->esOficial)
                            <span class="badge badge-blue"><i class="fa-solid fa-star"></i> Oficial FCF</span>
                        @endif
                    </div>
                    <div class="sesion-card__meta">
                        <span><i class="fa-regular fa-calendar"></i>{{ $sesion->fechaSesion->format('d/m/Y') }}</span>
                        <span><i class="fa-regular fa-clock"></i>{{ \Illuminate\Support\Carbon::parse($sesion->horaSesion)->format('H:i') }}</span>
                        <span><i class="fa-solid fa-{{ $sesion->modalidad === 'virtual' ? 'video' : 'location-dot' }}"></i>{{ $sesion->modalidad === 'virtual' ? 'Virtual' : ($sesion->lugar ?? 'Presencial') }}</span>
                    </div>
                    <div class="sesion-card__progreso-label">
                        <span>{{ $sesion->presentes_count }} de {{ $sesion->asistencias_count }} presentes</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="sesion-progreso-bar">
                        <div class="sesion-progreso-bar__fill" style="width:{{ $pct }}%;"></div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($sesiones->hasPages())
            <div class="pagination-wrapper">{{ $sesiones->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/academico/academico.js'])
@endpush
