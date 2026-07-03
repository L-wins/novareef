@extends('admin.layouts.app')

@section('titulo', $plan->nombre)

@section('contenido')

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

{{-- Volver --}}
<a href="{{ route('admin.planes.index') }}" class="admin-back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Volver a planes
</a>

@if(session('success'))
<div class="admin-alert admin-alert--success" style="margin-bottom:1.25rem;">
    <i class="fa-solid fa-circle-check"></i>
    {{ session('success') }}
</div>
@endif

{{-- Hero --}}
<div class="admin-detail-hero">
    <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
            <p class="admin-detail-hero__name" style="margin:0;">{{ $plan->nombre }}</p>
            <span class="badge badge--plan-{{ $planKey }}" style="font-size:0.75rem;padding:4px 10px;">
                {{ ucfirst($planKey) }}
            </span>
        </div>
        <p class="admin-detail-hero__code">
            ${{ number_format((float) $plan->precio, 0, ',', '.') }} / {{ $plan->periodicidad }}
        </p>
    </div>
    <div class="admin-detail-hero__actions">
        @if($plan->esActivo)
            <span class="badge badge--green" style="padding:5px 11px;font-size:0.75rem;">Activo</span>
        @else
            <span class="badge badge--gray" style="padding:5px 11px;font-size:0.75rem;">Inactivo</span>
        @endif
        @if($plan->esVisible)
            <span class="badge badge--blue" style="padding:5px 11px;font-size:0.75rem;">Visible</span>
        @else
            <span class="badge badge--gray" style="padding:5px 11px;font-size:0.75rem;">Oculto</span>
        @endif

        <a href="{{ route('admin.planes.edit', $plan->idPlan) }}" class="a-btn a-btn--ghost" style="height:38px;font-size:0.8125rem;">
            <i class="fa-solid fa-pen-to-square"></i>
            Editar
        </a>

        <form method="POST" action="{{ route('admin.planes.toggleVisible', $plan->idPlan) }}" style="display:contents;">
            @csrf @method('PUT')
            <button type="submit" class="a-btn a-btn--ghost" style="height:38px;font-size:0.8125rem;">
                <i class="fa-solid {{ $plan->esVisible ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                {{ $plan->esVisible ? 'Ocultar' : 'Mostrar' }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.planes.toggleActivo', $plan->idPlan) }}" style="display:contents;">
            @csrf @method('PUT')
            <button type="submit" class="a-btn a-btn--ghost" style="height:38px;font-size:0.8125rem;">
                <i class="fa-solid {{ $plan->esActivo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                {{ $plan->esActivo ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
    </div>
</div>

{{-- Mini stats --}}
<div class="admin-detail-mini-stats" style="margin-bottom:1.25rem;">
    <div class="admin-mini-stat">
        <div class="admin-mini-stat__value" style="color:var(--primary);">{{ $totalActivas }}</div>
        <div class="admin-mini-stat__label">Colegios activos / trial</div>
    </div>
    <div class="admin-mini-stat">
        <div class="admin-mini-stat__value">{{ $totalTrial }}</div>
        <div class="admin-mini-stat__label">En período trial</div>
    </div>
    <div class="admin-mini-stat">
        <div class="admin-mini-stat__value">{{ $totalHistorico }}</div>
        <div class="admin-mini-stat__label">Total histórico</div>
    </div>
</div>

{{-- Detalles del plan --}}
<div class="admin-detail-card" style="margin-bottom:1.25rem;">
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Configuración del plan</p>
        <div class="admin-detail-grid">
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Precio</span>
                <span class="admin-detail-field__value">${{ number_format((float) $plan->precio, 0, ',', '.') }} COP</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Periodicidad</span>
                <span class="admin-detail-field__value">{{ ucfirst($plan->periodicidad) }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Orden</span>
                <span class="admin-detail-field__value">{{ $plan->orden }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Límite árbitros</span>
                <span class="admin-detail-field__value">{{ $plan->limiteArbitrosTexto }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Límite cuentas admin</span>
                <span class="admin-detail-field__value">{{ $plan->limiteCuentasAdminTexto }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Incluye página web</span>
                <span class="admin-detail-field__value">{{ $plan->incluyePaginaWeb ? 'Sí' : 'No' }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Incluye onboarding</span>
                <span class="admin-detail-field__value">{{ $plan->incluyeOnboarding ? 'Sí' : 'No' }}</span>
            </div>
        </div>
    </div>

    @if($plan->modulosJSON && count($plan->modulosJSON))
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Módulos habilitados</p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($plan->modulosJSON as $modulo)
                <span class="plan-mgmt-modulo-tag plan-mgmt-modulo-tag--active">{{ $modulo }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Tabla de suscripciones --}}
<div class="admin-detail-card">
    <div class="admin-detail-section" style="border-bottom:1px solid var(--border-color);padding-bottom:1rem;">
        <p class="admin-detail-section__title" style="margin:0;">Colegios suscritos</p>
    </div>

    @if($suscripciones->isEmpty())
        <div style="padding:3rem;text-align:center;">
            <p style="color:var(--text-muted);font-size:0.875rem;">Ningún colegio ha contratado este plan aún.</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Colegio</th>
                        <th>Estado</th>
                        <th>Inicio</th>
                        <th>Vencimiento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suscripciones as $sus)
                    <tr>
                        <td>
                            <span style="font-weight:600;color:var(--text-bright);">
                                {{ $sus->colegio->nombreColegio ?? '—' }}
                            </span>
                            @if($sus->colegio)
                                <br><span style="font-size:0.75rem;color:var(--text-muted);font-family:monospace;">
                                    {{ $sus->colegio->codigoColegio }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($sus->estado === 'activa')
                                <span class="badge badge--green" style="font-size:0.7rem;">Activa</span>
                            @elseif($sus->estado === 'trial')
                                <span class="badge badge--amber" style="font-size:0.7rem;">Trial</span>
                            @elseif($sus->estado === 'vencida')
                                <span class="badge badge--red" style="font-size:0.7rem;">Vencida</span>
                            @elseif($sus->estado === 'cancelada')
                                <span class="badge badge--gray" style="font-size:0.7rem;">Cancelada</span>
                            @else
                                <span class="badge badge--gray" style="font-size:0.7rem;">{{ $sus->estado }}</span>
                            @endif
                        </td>
                        <td>{{ $sus->fechaInicio ? $sus->fechaInicio->format('d/m/Y') : '—' }}</td>
                        <td>{{ $sus->fechaVencimiento ? $sus->fechaVencimiento->format('d/m/Y') : '—' }}</td>
                        <td style="text-align:right;">
                            @if($sus->colegio)
                                <a href="{{ route('admin.colegios.show', $sus->colegio->idColegio) }}"
                                   class="a-tbl-btn" title="Ver colegio">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
