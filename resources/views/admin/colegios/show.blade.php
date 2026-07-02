@extends('admin.layouts.app')

@section('titulo', $colegio->nombreColegio)

@section('contenido')

{{-- Volver --}}
<a href="{{ route('admin.colegios.index') }}" class="admin-back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Volver a colegios
</a>

{{-- Hero --}}
<div class="admin-detail-hero">
    <div>
        <p class="admin-detail-hero__name">{{ $colegio->nombreColegio }}</p>
        <p class="admin-detail-hero__code">{{ $colegio->codigoColegio }}</p>
    </div>
    <div class="admin-detail-hero__actions">
        @if($colegio->estadoColegio === 'activo')
            <span class="badge badge--green" style="padding:5px 11px;font-size:0.75rem;">Activo</span>
        @elseif($colegio->estadoColegio === 'suspendido')
            <span class="badge badge--red" style="padding:5px 11px;font-size:0.75rem;">Suspendido</span>
        @elseif($colegio->estadoColegio === 'trial')
            <span class="badge badge--amber" style="padding:5px 11px;font-size:0.75rem;">Trial</span>
        @else
            <span class="badge badge--gray" style="padding:5px 11px;font-size:0.75rem;">{{ ucfirst($colegio->estadoColegio) }}</span>
        @endif

        <a href="{{ route('admin.colegios.edit', $colegio->idColegio) }}" class="a-btn a-btn--ghost" style="height:38px;font-size:0.8125rem;">
            <i class="fa-solid fa-pen-to-square"></i>
            Editar
        </a>

        {{-- Cambiar estado --}}
        <div style="position:relative;display:inline-block;" x-data="{ open: false }">
            <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="a-btn a-btn--ghost" style="height:38px;font-size:0.8125rem;">
                <i class="fa-solid fa-power-off"></i>
                Estado
                <i class="fa-solid fa-chevron-down" style="font-size:12px;"></i>
            </button>
            <div class="hidden" style="position:absolute;right:0;top:calc(100% + 6px);
                         background:var(--bg-navbar);border:1px solid var(--border-color);
                         border-radius:10px;min-width:160px;z-index:50;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.4);">
                @foreach(['activo' => ['label'=>'Activo','class'=>'badge--green'],
                           'suspendido' => ['label'=>'Suspendido','class'=>'badge--red'],
                           'inactivo' => ['label'=>'Inactivo','class'=>'badge--gray']] as $estado => $opts)
                @if($colegio->estadoColegio !== $estado)
                <form method="POST" action="{{ route('admin.colegios.toggleEstado', $colegio->idColegio) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="estado" value="{{ $estado }}">
                    <button type="submit" style="width:100%;padding:10px 14px;text-align:left;
                                display:flex;align-items:center;gap:8px;font-size:0.8125rem;color:var(--text);
                                background:none;border:none;cursor:pointer;transition:background .2s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.05)'"
                            onmouseout="this.style.background='none'">
                        <span class="badge {{ $opts['class'] }}" style="font-size:0.625rem;">{{ $opts['label'] }}</span>
                    </button>
                </form>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Sección 1: Datos del colegio --}}
<div class="admin-detail-card">
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Información general</p>
        <div class="admin-detail-grid">
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Nombre</span>
                <span class="admin-detail-field__value">{{ $colegio->nombreColegio }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Código</span>
                <span class="admin-detail-field__value" style="font-family:monospace;font-size:0.8125rem;">
                    {{ $colegio->codigoColegio }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Tenant ID</span>
                <span class="admin-detail-field__value" style="font-family:monospace;font-size:0.8125rem;color:var(--text);">
                    {{ $colegio->tenantId }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Correo electrónico</span>
                <span class="admin-detail-field__value">{{ $colegio->emailColegio }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Teléfono</span>
                @if($colegio->telefonoColegio)
                    <span class="admin-detail-field__value">{{ $colegio->telefonoColegio }}</span>
                @else
                    <span class="admin-detail-field__empty">No registrado</span>
                @endif
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Registrado</span>
                <span class="admin-detail-field__value">{{ $colegio->created_at?->format('d/m/Y') ?? '—' }}</span>
            </div>
        </div>
    </div>

    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Ubicación</p>
        <div class="admin-detail-grid">
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">País</span>
                <span class="admin-detail-field__value">{{ $colegio->paisColegio }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Departamento</span>
                @if($colegio->departamentoColegio)
                    <span class="admin-detail-field__value">{{ $colegio->departamentoColegio }}</span>
                @else
                    <span class="admin-detail-field__empty">No registrado</span>
                @endif
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Ciudad</span>
                @if($colegio->ciudadColegio)
                    <span class="admin-detail-field__value">{{ $colegio->ciudadColegio }}</span>
                @else
                    <span class="admin-detail-field__empty">No registrada</span>
                @endif
            </div>
            <div class="admin-detail-field admin-detail-col-2">
                <span class="admin-detail-field__label">Dirección</span>
                @if($colegio->direccionColegio)
                    <span class="admin-detail-field__value">{{ $colegio->direccionColegio }}</span>
                @else
                    <span class="admin-detail-field__empty">No registrada</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Sección 2: Suscripción --}}
@php $suscripcion = $colegio->suscripcionActiva; $planActual = $suscripcion?->plan; @endphp
<div class="admin-detail-card">
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Suscripción actual</p>
        @if($suscripcion && $planActual)
        @php $planKey = strtolower($planActual->nombre); @endphp
        <div class="admin-detail-grid">
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Plan</span>
                <span class="admin-detail-field__value">
                    <span class="badge badge--plan-{{ $planKey }}">{{ $planActual->nombre }}</span>
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Precio</span>
                <span class="admin-detail-field__value">
                    ${{ number_format($planActual->precio, 0, ',', '.') }} COP
                    / {{ $planActual->periodicidad }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Estado suscripción</span>
                <span class="admin-detail-field__value">
                    @if($suscripcion->estado === 'activa')
                        <span class="badge badge--green">Activa</span>
                    @elseif($suscripcion->estado === 'trial')
                        <span class="badge badge--amber">Trial</span>
                    @elseif($suscripcion->estado === 'vencida')
                        <span class="badge badge--red">Vencida</span>
                    @else
                        <span class="badge badge--gray">{{ ucfirst($suscripcion->estado) }}</span>
                    @endif
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Inicio</span>
                <span class="admin-detail-field__value">
                    {{ $suscripcion->fechaInicio?->format('d/m/Y') ?? '—' }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Vencimiento</span>
                <span class="admin-detail-field__value">
                    {{ $suscripcion->fechaVencimiento?->format('d/m/Y') ?? '—' }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Vigente</span>
                <span class="admin-detail-field__value">
                    @if($suscripcion->estaVigente())
                        <span class="badge badge--green">Sí</span>
                    @else
                        <span class="badge badge--red">No</span>
                    @endif
                </span>
            </div>
        </div>
        @else
        <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">Este colegio no tiene suscripción activa.</p>
        @endif
    </div>
</div>

{{-- Sección 3: Administrador --}}
<div class="admin-detail-card">
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Administrador del colegio</p>
        @if($admin)
        <div class="admin-detail-grid admin-detail-grid--2">
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Nombre</span>
                <span class="admin-detail-field__value">{{ $admin->nombreUsuario }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Correo</span>
                <span class="admin-detail-field__value">{{ $admin->emailUsuario }}</span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Estado</span>
                <span class="admin-detail-field__value">
                    @if($admin->estadoUsuario === 'activo')
                        <span class="badge badge--green">Activo</span>
                    @else
                        <span class="badge badge--gray">{{ ucfirst($admin->estadoUsuario) }}</span>
                    @endif
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Último acceso</span>
                @if($admin->ultimoAcceso)
                    <span class="admin-detail-field__value">{{ $admin->ultimoAcceso->format('d/m/Y H:i') }}</span>
                @else
                    <span class="admin-detail-field__empty">Sin accesos registrados</span>
                @endif
            </div>
        </div>
        @else
        <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">No se encontró un administrador ejecutivo para este colegio.</p>
        @endif
    </div>
</div>

{{-- Sección 4: Estadísticas de árbitros --}}
<div class="admin-detail-card">
    <div class="admin-detail-section">
        <p class="admin-detail-section__title">Árbitros</p>
        <div class="admin-detail-mini-stats">
            <div class="admin-mini-stat">
                <div class="admin-mini-stat__value">{{ $totalArbitros }}</div>
                <div class="admin-mini-stat__label">Total árbitros</div>
            </div>
            <div class="admin-mini-stat">
                <div class="admin-mini-stat__value" style="color:var(--success);">{{ $arbitrosActivos }}</div>
                <div class="admin-mini-stat__label">Activos</div>
            </div>
            <div class="admin-mini-stat">
                <div class="admin-mini-stat__value" style="color:var(--warning);">{{ $arbitrosProceso }}</div>
                <div class="admin-mini-stat__label">En proceso de ingreso</div>
            </div>
        </div>
    </div>
</div>

@endsection
