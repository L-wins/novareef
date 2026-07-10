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
            <span class="badge badge--green badge--lg">Activo</span>
        @elseif($colegio->estadoColegio === 'suspendido')
            <span class="badge badge--red badge--lg">Suspendido</span>
        @elseif($colegio->estadoColegio === 'prueba')
            <span class="badge badge--amber badge--lg">Prueba</span>
        @else
            <span class="badge badge--gray badge--lg">{{ ucfirst($colegio->estadoColegio) }}</span>
        @endif

        <a href="{{ route('admin.colegios.edit', $colegio->idColegio) }}" class="a-btn a-btn--ghost a-btn--sm">
            <i class="fa-solid fa-pen-to-square"></i>
            Editar
        </a>

        <form method="POST" action="{{ route('admin.colegios.impersonar', $colegio->idColegio) }}"
              data-confirm-submit
              data-confirm-title="Entrar como colegio"
              data-confirm-text="¿Entrar como la cuenta ejecutivo de «{{ $colegio->nombreColegio }}»? Se registrará en el log de impersonaciones."
              data-confirm-btn="Sí, entrar">
            @csrf
            <button type="submit" class="a-btn a-btn--ghost a-btn--sm">
                <i class="fa-solid fa-user-secret"></i>
                Entrar como
            </button>
        </form>

        {{-- Cambiar estado --}}
        <div class="admin-dropdown" data-dropdown>
            <button type="button" data-dropdown-toggle class="a-btn a-btn--ghost a-btn--sm">
                <i class="fa-solid fa-power-off"></i>
                Estado
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="admin-dropdown__menu hidden">
                @foreach(['activo' => ['label'=>'Activo','class'=>'badge--green'],
                           'suspendido' => ['label'=>'Suspendido','class'=>'badge--red']] as $estado => $opts)
                @if($colegio->estadoColegio !== $estado)
                <form method="POST" action="{{ route('admin.colegios.toggleEstado', $colegio->idColegio) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="estado" value="{{ $estado }}">
                    <button type="submit" class="admin-dropdown__item">
                        <span class="badge {{ $opts['class'] }}">{{ $opts['label'] }}</span>
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
                <span class="admin-detail-field__value admin-detail-field__value--mono">
                    {{ $colegio->codigoColegio }}
                </span>
            </div>
            <div class="admin-detail-field">
                <span class="admin-detail-field__label">Tenant ID</span>
                <span class="admin-detail-field__value admin-detail-field__value--mono admin-detail-field__value--dim">
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
        <p class="admin-detail-empty">Este colegio no tiene suscripción activa.</p>
        @endif

        {{-- Acciones de suscripción --}}
        <div class="admin-detail-actions">

            <form method="POST" action="{{ route('admin.suscripciones.cambiarPlan', $colegio->idColegio) }}"
                  data-confirm-submit
                  data-confirm-title="Cambiar plan"
                  data-confirm-text="¿Cambiar el plan de «{{ $colegio->nombreColegio }}»? La suscripción actual queda como histórico."
                  data-confirm-btn="Sí, cambiar plan">
                @csrf
                @method('PUT')
                <div class="admin-filter">
                    <select name="idPlan" data-nova-select data-placeholder="Cambiar a…" required>
                        <option value="">Cambiar a…</option>
                        @foreach($planesDisponibles as $p)
                            <option value="{{ $p->idPlan }}" {{ $planActual?->idPlan === $p->idPlan ? 'disabled' : '' }}>
                                {{ $p->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="a-btn a-btn--ghost">
                    <i class="fa-solid fa-right-left"></i> Cambiar plan
                </button>
            </form>

            <form method="POST" action="{{ route('admin.suscripciones.extender', $colegio->idColegio) }}">
                @csrf
                @method('PUT')
                <input type="number" name="dias" min="1" max="365" value="30" required
                       class="admin-input admin-input--xs" title="Días a extender">
                <button type="submit" class="a-btn a-btn--ghost">
                    <i class="fa-solid fa-calendar-plus"></i> Extender
                </button>
            </form>

            @if($suscripcion && $suscripcion->estaVigente())
            <form method="POST" action="{{ route('admin.suscripciones.cancelar', $colegio->idColegio) }}"
                  data-confirm-submit
                  data-confirm-title="Cancelar suscripción"
                  data-confirm-text="¿Cancelar la suscripción de «{{ $colegio->nombreColegio }}»? El colegio perderá el acceso."
                  data-confirm-color="#ef4444"
                  data-confirm-btn="Sí, cancelar">
                @csrf
                @method('PUT')
                <button type="submit" class="a-btn a-btn--danger-soft">
                    <i class="fa-solid fa-ban"></i> Cancelar suscripción
                </button>
            </form>
            @endif

        </div>
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
        <p class="admin-detail-empty">No se encontró un administrador ejecutivo para este colegio.</p>
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
                <div class="admin-mini-stat__value admin-mini-stat__value--success">{{ $arbitrosActivos }}</div>
                <div class="admin-mini-stat__label">Activos</div>
            </div>
            <div class="admin-mini-stat">
                <div class="admin-mini-stat__value admin-mini-stat__value--warning">{{ $arbitrosProceso }}</div>
                <div class="admin-mini-stat__label">En proceso de ingreso</div>
            </div>
        </div>
    </div>
</div>

@endsection
