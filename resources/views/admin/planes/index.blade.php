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
<div class="admin-alert admin-alert--success" style="margin-bottom:1.25rem;">
    <i data-feather="check-circle"></i>
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
                    <span class="badge badge--green" style="font-size:0.65rem;padding:3px 8px;">Activo</span>
                @else
                    <span class="badge badge--gray" style="font-size:0.65rem;padding:3px 8px;">Inactivo</span>
                @endif
                @if($plan->esVisible)
                    <span class="badge badge--blue" style="font-size:0.65rem;padding:3px 8px;">Visible</span>
                @else
                    <span class="badge badge--gray" style="font-size:0.65rem;padding:3px 8px;">Oculto</span>
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
                <span class="plan-mgmt-stat__value">{{ $plan->limiteRolesTexto }}</span>
                <span class="plan-mgmt-stat__label">Roles</span>
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
                <i data-feather="eye"></i>
                Ver
            </a>
            <a href="{{ route('admin.planes.edit', $plan->idPlan) }}" class="a-btn a-btn--ghost plan-mgmt-card__btn">
                <i data-feather="edit-2"></i>
                Editar
            </a>
            <form method="POST" action="{{ route('admin.planes.toggleActivo', $plan->idPlan) }}" style="display:contents;">
                @csrf
                @method('PUT')
                <button type="submit" class="a-btn a-btn--ghost plan-mgmt-card__btn"
                        title="{{ $plan->esActivo ? 'Desactivar plan' : 'Activar plan' }}">
                    <i data-feather="{{ $plan->esActivo ? 'toggle-right' : 'toggle-left' }}"></i>
                    {{ $plan->esActivo ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>

    </div>
@empty
    <div class="admin-card" style="padding:3rem;text-align:center;grid-column:1/-1;">
        <p style="color:var(--text-muted);font-size:0.875rem;">No hay planes registrados.</p>
    </div>
@endforelse
</div>

@endsection
