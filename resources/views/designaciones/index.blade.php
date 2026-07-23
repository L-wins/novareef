@extends('layouts.app')

@section('titulo', 'Designaciones')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container desi-shell">
    @php
        $torneosCount = $torneos->count();
        $partidosCount = $torneos->sum('partidos_count');
        $partidosHoyCount = $torneos->sum('partidos_hoy_count');
    @endphp

    {{-- ═══ HERO ═══ --}}
    <div class="desi-hero">
        <div class="desi-hero__main">
            <div class="desi-hero__icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div class="desi-hero__left">
                <div class="desi-hero__eyebrow">
                    @if($criticosCount > 0)
                    <span class="desi-alerta-critico">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        {{ $criticosCount }} crítico{{ $criticosCount > 1 ? 's' : '' }}
                    </span>
                    @endif
                    <span class="desi-hero__label">Gestión de partidos</span>
                </div>
                <h1 class="desi-hero__title">Designaciones</h1>
                <p class="desi-hero__sub">Elige un torneo para ver y gestionar sus partidos.</p>
            </div>
        </div>

        @can('crear-designaciones')
        <div class="desi-hero__acciones">
            <a href="{{ route('designaciones.importar.mostrar') }}" class="btn btn-ghost desi-action-btn">
                <i class="fa-solid fa-file-word"></i>
                Importar desde Word
            </a>
            <a href="{{ route('designaciones.create') }}" class="btn btn-primary desi-action-btn">
                <i class="fa-solid fa-plus"></i>
                Nuevo partido
            </a>
        </div>
        @endcan
    </div>

    <div class="desi-overview-grid" aria-label="Resumen de designaciones">
        <div class="desi-overview-card">
            <span class="desi-overview-card__label">Torneos</span>
            <strong>{{ $torneosCount }}</strong>
        </div>
        <div class="desi-overview-card">
            <span class="desi-overview-card__label">Partidos</span>
            <strong>{{ $partidosCount }}</strong>
        </div>
        <div class="desi-overview-card">
            <span class="desi-overview-card__label">Hoy</span>
            <strong>{{ $partidosHoyCount }}</strong>
        </div>
        <div class="desi-overview-card {{ $criticosCount > 0 ? 'desi-overview-card--danger' : '' }}">
            <span class="desi-overview-card__label">Críticos</span>
            <strong>{{ $criticosCount }}</strong>
        </div>
    </div>

    {{-- ═══ GRID DE TORNEOS ═══ --}}
    <div class="desi-torneos-grid">
        @forelse($torneos as $torneo)
        <a href="{{ route('designaciones.index', ['torneo' => $torneo->idTorneo]) }}" class="desi-torneo-card">
            <div class="desi-torneo-card__top">
                <div class="desi-torneo-card__badge-row">
                    <span class="desi-torneo-card__estado desi-torneo-card__estado--{{ $torneo->estadoTorneo }}">
                        {{ ucfirst($torneo->estadoTorneo) }}
                    </span>
                    @if($torneo->tipoTorneo === 'oficial')
                    <span class="desi-oficial-badge"><i class="fa-solid fa-shield-halved"></i> Oficial</span>
                    @endif
                </div>
                <span class="desi-torneo-card__icon">
                    <i class="fa-solid fa-trophy"></i>
                </span>
            </div>

            <h2 class="desi-torneo-card__nombre">{{ $torneo->nombreTorneo }}</h2>
            <div class="desi-torneo-card__meta">
                <span><i class="fa-regular fa-calendar"></i> Temporada {{ $torneo->temporada }}</span>
            </div>

            <div class="desi-torneo-card__bottom">
                <span class="desi-torneo-card__count">
                    <strong>{{ $torneo->partidos_count }}</strong> partido{{ $torneo->partidos_count !== 1 ? 's' : '' }}
                </span>
                @if($torneo->partidos_hoy_count > 0)
                <span class="desi-hoy-badge">
                    <i class="fa-solid fa-circle desi-dot-icon"></i>
                    {{ $torneo->partidos_hoy_count }} hoy
                </span>
                @endif
                @if($torneo->partidos_criticos_count > 0)
                <span class="desi-alerta-critico desi-alerta-critico--compact">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ $torneo->partidos_criticos_count }}
                </span>
                @endif
                <span class="desi-torneo-card__arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </span>
            </div>
        </a>
        @empty
        <div class="empty-state empty-state--designaciones">
            <span class="empty-state__icon">
                <i class="fa-solid fa-trophy"></i>
            </span>
            <p class="empty-state__title">No hay torneos registrados</p>
            <p class="empty-state__sub">Crea un torneo primero para poder designar partidos.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
