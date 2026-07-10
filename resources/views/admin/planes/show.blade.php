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
<div class="admin-alert admin-alert--success mb-4">
    <i class="fa-solid fa-circle-check"></i>
    {{ session('success') }}
</div>
@endif

{{-- Hero --}}
<div class="admin-detail-hero">
    <div>
        <div class="admin-detail-hero__title-row">
            <p class="admin-detail-hero__name">{{ $plan->nombre }}</p>
            <span class="badge badge--plan-{{ $planKey }} badge--lg">
                {{ ucfirst($planKey) }}
            </span>
        </div>
        <p class="admin-detail-hero__code">
            ${{ number_format((float) $plan->precio, 0, ',', '.') }} / {{ $plan->periodicidad }}
        </p>
    </div>
    <div class="admin-detail-hero__actions">
        @if($plan->esActivo)
            <span class="badge badge--green badge--lg">Activo</span>
        @else
            <span class="badge badge--gray badge--lg">Inactivo</span>
        @endif
        @if($plan->esVisible)
            <span class="badge badge--blue badge--lg">Visible</span>
        @else
            <span class="badge badge--gray badge--lg">Oculto</span>
        @endif

        <a href="{{ route('admin.planes.edit', $plan->idPlan) }}" class="a-btn a-btn--ghost a-btn--sm">
            <i class="fa-solid fa-pen-to-square"></i>
            Editar
        </a>

        <form method="POST" action="{{ route('admin.planes.toggleVisible', $plan->idPlan) }}" class="form-contents">
            @csrf @method('PUT')
            <button type="submit" class="a-btn a-btn--ghost a-btn--sm">
                <i class="fa-solid {{ $plan->esVisible ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                {{ $plan->esVisible ? 'Ocultar' : 'Mostrar' }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.planes.toggleActivo', $plan->idPlan) }}" class="form-contents">
            @csrf @method('PUT')
            <button type="submit" class="a-btn a-btn--ghost a-btn--sm">
                <i class="fa-solid {{ $plan->esActivo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                {{ $plan->esActivo ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
    </div>
</div>

{{-- Mini stats --}}
<div class="admin-detail-mini-stats mb-4">
    <div class="admin-mini-stat">
        <div class="admin-mini-stat__value admin-mini-stat__value--accent">{{ $totalActivas }}</div>
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
<div class="admin-detail-card">
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
        <div class="plan-mgmt-card__modulos">
            @foreach($plan->modulosJSON as $modulo)
                <span class="plan-mgmt-modulo-tag plan-mgmt-modulo-tag--active">{{ $modulo }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Tabla de suscripciones --}}
<div class="admin-detail-card">
    <div class="admin-detail-section admin-detail-section--compact">
        <p class="admin-detail-section__title">Colegios suscritos</p>
    </div>

    @if($suscripciones->isEmpty())
        <div class="admin-card--empty">
            <p>Ningún colegio ha contratado este plan aún.</p>
        </div>
    @else
        <div class="admin-table-scroll">
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
                            <span class="admin-table__strong">
                                {{ $sus->colegio->nombreColegio ?? '—' }}
                            </span>
                            @if($sus->colegio)
                                <br><span class="admin-table__mono">
                                    {{ $sus->colegio->codigoColegio }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($sus->estado === 'activa')
                                <span class="badge badge--green">Activa</span>
                            @elseif($sus->estado === 'trial')
                                <span class="badge badge--amber">Trial</span>
                            @elseif($sus->estado === 'vencida')
                                <span class="badge badge--red">Vencida</span>
                            @elseif($sus->estado === 'cancelada')
                                <span class="badge badge--gray">Cancelada</span>
                            @else
                                <span class="badge badge--gray">{{ $sus->estado }}</span>
                            @endif
                        </td>
                        <td>{{ $sus->fechaInicio ? $sus->fechaInicio->format('d/m/Y') : '—' }}</td>
                        <td>{{ $sus->fechaVencimiento ? $sus->fechaVencimiento->format('d/m/Y') : '—' }}</td>
                        <td class="text-right">
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
