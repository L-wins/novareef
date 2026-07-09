@extends('admin.layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')

{{-- Encabezado --}}
<div class="admin-page-header">
    <h1>Bienvenido, {{ Auth::guard('admin')->user()->nombre }}</h1>
    <p>
        {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        &nbsp;·&nbsp;
        @if(Auth::guard('admin')->user()->two_factor_enabled)
            <span class="badge badge--green">2FA activo</span>
        @else
            <a href="{{ route('admin.2fa.config') }}" class="badge badge--red">
                2FA inactivo — configúralo ahora
            </a>
        @endif
    </p>
</div>

{{-- Tarjetas de estadísticas --}}
<div class="admin-stats-grid">

    <div class="stat-card blue">
        <div class="stat-card__head">
            <span class="stat-card__label">Total Colegios</span>
            <div class="stat-card__icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
        <div class="stat-card__value">{{ $totalColegios }}</div>
        <div class="stat-card__sub">Colegios registrados</div>
    </div>

    <div class="stat-card green">
        <div class="stat-card__head">
            <span class="stat-card__label">Colegios Activos</span>
            <div class="stat-card__icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="stat-card__value">{{ $colegiosActivos }}</div>
        <div class="stat-card__sub">Estado activo</div>
    </div>

    <div class="stat-card amber">
        <div class="stat-card__head">
            <span class="stat-card__label">En Trial</span>
            <div class="stat-card__icon">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>
        <div class="stat-card__value">{{ $colegiosTrial }}</div>
        <div class="stat-card__sub">Suscripciones trial</div>
    </div>

    <div class="stat-card purple">
        <div class="stat-card__head">
            <span class="stat-card__label">Total Árbitros</span>
            <div class="stat-card__icon">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="stat-card__value">{{ $totalArbitros }}</div>
        <div class="stat-card__sub">En todo el sistema</div>
    </div>

</div>

{{-- Tabla: últimos colegios --}}
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Últimos colegios registrados</h2>
        <a href="{{ route('admin.colegios.index') }}" class="admin-card__link">
            Ver todos
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Código</th>
                <th>País</th>
                <th>Estado</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ultimosColegios as $colegio)
            <tr>
                <td>
                    <div class="admin-table__strong">
                        {{ $colegio->nombreColegio }}
                    </div>
                    <div class="admin-table__sub">
                        {{ $colegio->emailColegio }}
                    </div>
                </td>
                <td>
                    <code class="admin-table__mono">
                        {{ $colegio->codigoColegio }}
                    </code>
                </td>
                <td>{{ $colegio->paisColegio }}</td>
                <td>
                    @if($colegio->estadoColegio === 'activo')
                        <span class="badge badge--green">Activo</span>
                    @elseif($colegio->estadoColegio === 'suspendido')
                        <span class="badge badge--red">Suspendido</span>
                    @else
                        <span class="badge badge--gray">{{ $colegio->estadoColegio }}</span>
                    @endif
                </td>
                <td class="admin-table__muted">
                    {{ $colegio->created_at?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="admin-table__empty">
                    No hay colegios registrados aún.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
