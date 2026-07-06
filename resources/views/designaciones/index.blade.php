@extends('layouts.app')

@section('titulo', 'Designaciones')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- ═══ HERO ═══ --}}
    <div class="desi-hero">
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

        @can('crear-designaciones')
        <a href="{{ route('designaciones.create') }}" class="btn btn-primary desi-btn-nuevo">
            <i class="fa-solid fa-plus"></i>
            Nuevo partido
        </a>
        @endcan
    </div>

    {{-- ═══ GRID DE TORNEOS ═══ --}}
    <div class="desi-torneos-grid">
        @forelse($torneos as $torneo)
        <a href="{{ route('designaciones.index', ['torneo' => $torneo->idTorneo]) }}" class="desi-torneo-card">
            <div class="desi-torneo-card__top">
                <span class="desi-torneo-card__estado desi-torneo-card__estado--{{ $torneo->estadoTorneo }}">
                    {{ ucfirst($torneo->estadoTorneo) }}
                </span>
                @if($torneo->partidos_criticos_count > 0)
                <span class="desi-alerta-critico">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ $torneo->partidos_criticos_count }}
                </span>
                @endif
            </div>

            <h2 class="desi-torneo-card__nombre">{{ $torneo->nombreTorneo }}</h2>
            <div class="desi-torneo-card__meta">
                <span><i class="fa-regular fa-calendar"></i> Temporada {{ $torneo->temporada }}</span>
                @if($torneo->tipoTorneo === 'oficial')
                <span class="desi-oficial-badge"><i class="fa-solid fa-shield-halved"></i> Oficial</span>
                @endif
            </div>

            <div class="desi-torneo-card__bottom">
                <span class="desi-torneo-card__count">
                    <strong>{{ $torneo->partidos_count }}</strong> partido{{ $torneo->partidos_count !== 1 ? 's' : '' }}
                </span>
                @if($torneo->partidos_hoy_count > 0)
                <span class="desi-hoy-badge"><i class="fa-solid fa-circle" style="font-size:.45rem"></i> {{ $torneo->partidos_hoy_count }} hoy</span>
                @endif
                <i class="fa-solid fa-arrow-right desi-torneo-card__arrow"></i>
            </div>
        </a>
        @empty
        <div class="empty-state">
            <i class="fa-solid fa-trophy" style="font-size:3rem;color:var(--text-muted);margin-bottom:1.25rem"></i>
            <p class="empty-state__title">No hay torneos registrados</p>
            <p class="empty-state__sub">Crea un torneo primero para poder designar partidos.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
