@extends('admin.layouts.app')

@section('titulo', 'Planes')

@section('contenido')

<div class="admin-page-header">
    <div>
        <h1>Planes de suscripción</h1>
        <p>Configura los planes disponibles para los colegios de árbitros.</p>
    </div>
</div>

@if(session('success'))
<div class="admin-alert admin-alert--success">
    <i class="fa-solid fa-circle-check"></i>
    {{ session('success') }}
</div>
@endif

<div class="plan-mgmt-grid">
@forelse($planes as $plan)
    @php
        $slug = strtolower($plan->nombre);
        if (str_contains($slug, 'godmode') || str_contains($slug, 'god mode')) {
            $planKey = 'godmode';
        } elseif (str_contains($slug, 'zenith')) {
            $planKey = 'zenith';
        } elseif (str_contains($slug, 'goliath')) {
            $planKey = 'goliath';
        } else {
            $planKey = 'rookie';
        }
    @endphp
    <div class="plan-mgmt-card plan-mgmt-card--{{ $planKey }}">

        {{-- Header --}}
        <div class="plan-mgmt-card__header">
            <span class="badge badge--plan-{{ $planKey }} plan-mgmt-card__name-badge">
                {{ $plan->nombre }}
            </span>
            <div class="plan-mgmt-card__status-row">
                @if($plan->esActivo)
                    <span class="badge badge--green badge--sm">Activo</span>
                @else
                    <span class="badge badge--gray badge--sm">Inactivo</span>
                @endif
                @if($plan->esVisible)
                    <span class="badge badge--blue badge--sm">Visible</span>
                @else
                    <span class="badge badge--gray badge--sm">Oculto</span>
                @endif
            </div>
        </div>

        {{-- Precio --}}
        <div class="plan-mgmt-card__price">
            <span class="plan-mgmt-card__price-value">
                ${{ number_format((float) $plan->precio, 0, ',', '.') }}
            </span>
            <span class="plan-mgmt-card__price-period">
                / {{ $plan->periodicidad }}
            </span>
        </div>

        {{-- Stats --}}
        <div class="plan-mgmt-card__stats">
            <div class="plan-mgmt-stat">
                <span class="plan-mgmt-stat__value">{{ $plan->colegios_suscritos }}</span>
                <span class="plan-mgmt-stat__label">Colegios activos</span>
            </div>
            <div class="plan-mgmt-stat">
                <span class="plan-mgmt-stat__value">{{ $plan->limiteArbitrosTexto }}</span>
                <span class="plan-mgmt-stat__label">Árbitros</span>
            </div>
            <div class="plan-mgmt-stat">
                <span class="plan-mgmt-stat__value">{{ $plan->limiteCuentasAdminTexto }}</span>
                <span class="plan-mgmt-stat__label">Cuentas admin</span>
            </div>
        </div>

        {{-- Módulos --}}
        @if($plan->modulosJSON && count($plan->modulosJSON))
        <div class="plan-mgmt-card__modulos">
            @foreach($plan->modulosJSON as $modulo)
                <span class="plan-mgmt-modulo-tag">{{ $modulo }}</span>
            @endforeach
        </div>
        @endif

        {{-- Acciones --}}
        <div class="plan-mgmt-card__footer">
            <a href="{{ route('admin.planes.show', $plan->idPlan) }}" class="a-btn a-btn--ghost plan-mgmt-card__btn">
                <i class="fa-solid fa-eye"></i>
                Ver
            </a>
            <a href="{{ route('admin.planes.edit', $plan->idPlan) }}" class="a-btn a-btn--ghost plan-mgmt-card__btn">
                <i class="fa-solid fa-pen-to-square"></i>
                Editar
            </a>
            <form method="POST" action="{{ route('admin.planes.toggleActivo', $plan->idPlan) }}" class="form-contents">
                @csrf
                @method('PUT')
                <button type="submit" class="a-btn a-btn--ghost plan-mgmt-card__btn"
                        title="{{ $plan->esActivo ? 'Desactivar plan' : 'Activar plan' }}">
                    <i class="fa-solid {{ $plan->esActivo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $plan->esActivo ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>

    </div>
@empty
    <div class="admin-card admin-card--empty">
        <p>No hay planes registrados.</p>
    </div>
@endforelse
</div>

@endsection
