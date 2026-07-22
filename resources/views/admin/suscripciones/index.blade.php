@extends('admin.layouts.app')

@section('titulo', 'Suscripciones')

@section('contenido')

<div class="admin-page-header admin-page-header--row">
    <div>
        <h1>Suscripciones</h1>
        <p>Historial de suscripciones de todos los colegios. Cambiar de plan, extender o cancelar se hace desde el detalle de cada colegio.</p>
    </div>
    <a href="{{ route('admin.suscripciones.exportar', request()->query()) }}" class="a-btn a-btn--ghost">
        <i class="fa-solid fa-file-arrow-down"></i>
        Exportar CSV
    </a>
</div>

{{-- Resumen — cada tarjeta filtra al hacer clic (reinicia el resto de filtros) --}}
<div class="admin-stats-grid">

    <a href="{{ route('admin.suscripciones.index', ['estado' => 'activa']) }}"
       class="stat-card green {{ request('estado') === 'activa' ? 'stat-card--active' : '' }}">
        <div class="stat-card__head">
            <span class="stat-card__label">Activas</span>
            <div class="stat-card__icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="stat-card__value">{{ $resumen['activas'] }}</div>
        <div class="stat-card__sub">Al día, plan pago</div>
    </a>

    <a href="{{ route('admin.suscripciones.index', ['estado' => 'trial']) }}"
       class="stat-card amber {{ request('estado') === 'trial' ? 'stat-card--active' : '' }}">
        <div class="stat-card__head">
            <span class="stat-card__label">En trial</span>
            <div class="stat-card__icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="stat-card__value">{{ $resumen['trial'] }}</div>
        <div class="stat-card__sub">Prueba gratuita en curso</div>
    </a>

    <a href="{{ route('admin.suscripciones.index', ['vencimiento' => $diasVencePronto]) }}"
       class="stat-card orange {{ request('vencimiento') ? 'stat-card--active' : '' }}">
        <div class="stat-card__head">
            <span class="stat-card__label">Vencen pronto</span>
            <div class="stat-card__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
        <div class="stat-card__value">{{ $resumen['vencenPronto'] }}</div>
        <div class="stat-card__sub">En los próximos {{ $diasVencePronto }} días</div>
    </a>

    <a href="{{ route('admin.suscripciones.index', ['estado' => 'vencida']) }}"
       class="stat-card red {{ request('estado') === 'vencida' ? 'stat-card--active' : '' }}">
        <div class="stat-card__head">
            <span class="stat-card__label">Vencidas</span>
            <div class="stat-card__icon"><i class="fa-solid fa-circle-xmark"></i></div>
        </div>
        <div class="stat-card__value">{{ $resumen['vencidas'] }}</div>
        <div class="stat-card__sub">Sin acceso al sistema</div>
    </a>

</div>

{{-- Filtros --}}
<div class="admin-card admin-card--filters">
    <form method="GET" action="{{ route('admin.suscripciones.index') }}" data-auto-filter class="admin-filters">
        <div class="admin-search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar colegio…"
                   class="admin-search-input"
                   autocomplete="off">
            @if(request('q'))
                <a href="{{ route('admin.suscripciones.index', request()->except('q')) }}" class="admin-search-clear" title="Limpiar búsqueda">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>
        <div class="admin-filter">
            <select name="estado" data-nova-select data-placeholder="Todos los estados">
                <option value="">Todos los estados</option>
                <option value="activa"     {{ request('estado') === 'activa'     ? 'selected' : '' }}>Activa</option>
                <option value="trial"      {{ request('estado') === 'trial'      ? 'selected' : '' }}>Trial</option>
                <option value="vencida"    {{ request('estado') === 'vencida'    ? 'selected' : '' }}>Vencida</option>
                <option value="suspendida" {{ request('estado') === 'suspendida' ? 'selected' : '' }}>Suspendida</option>
            </select>
        </div>
        <div class="admin-filter">
            <select name="plan" data-nova-select data-placeholder="Todos los planes">
                <option value="">Todos los planes</option>
                @foreach($planes as $p)
                    <option value="{{ $p->idPlan }}" {{ (string) request('plan') === (string) $p->idPlan ? 'selected' : '' }}>
                        {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="admin-filter">
            <select name="vencimiento" data-nova-select data-placeholder="Cualquier vencimiento">
                <option value="">Cualquier vencimiento</option>
                <option value="7"  {{ (string) request('vencimiento') === '7'  ? 'selected' : '' }}>Vence en 7 días</option>
                <option value="30" {{ (string) request('vencimiento') === '30' ? 'selected' : '' }}>Vence en 30 días</option>
            </select>
        </div>
    </form>
</div>

{{-- Tabla --}}
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Colegio</th>
                <th>Plan</th>
                <th>Estado</th>
                <th>Inicio</th>
                <th>Vencimiento</th>
                <th class="text-right">Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suscripciones as $s)
            @php
                $planKey = strtolower($s->plan?->nombre ?? '');

                // Etiqueta de urgencia: cuánto falta (o hace cuánto pasó) el
                // vencimiento — mucho más accionable que solo la fecha cruda.
                $diasRestantes = $s->fechaVencimiento ? today()->diffInDays($s->fechaVencimiento, false) : null;
                if ($diasRestantes === null) {
                    $urgenciaTexto = null; $urgenciaBadge = null;
                } elseif ($diasRestantes < 0) {
                    $urgenciaTexto = 'Vencida hace ' . abs($diasRestantes) . ' ' . (abs($diasRestantes) === 1 ? 'día' : 'días');
                    $urgenciaBadge = 'badge--red';
                } elseif ($diasRestantes === 0) {
                    $urgenciaTexto = 'Vence hoy'; $urgenciaBadge = 'badge--red';
                } elseif ($diasRestantes <= $diasVencePronto) {
                    $urgenciaTexto = 'Vence en ' . $diasRestantes . ' ' . ($diasRestantes === 1 ? 'día' : 'días');
                    $urgenciaBadge = 'badge--amber';
                } else {
                    $urgenciaTexto = null; $urgenciaBadge = null;
                }
            @endphp
            <tr>
                <td class="admin-table__strong">{{ $s->colegio?->nombreColegio ?? '—' }}</td>
                <td>
                    @if($s->plan)
                        <span class="badge badge--plan-{{ $planKey }}">{{ $s->plan->nombre }}</span>
                    @else
                        <span class="badge badge--gray">Sin plan</span>
                    @endif
                </td>
                <td>
                    @if($s->estado === 'activa')
                        <span class="badge badge--green">Activa</span>
                    @elseif($s->estado === 'trial')
                        <span class="badge badge--amber">Trial</span>
                    @elseif($s->estado === 'vencida')
                        <span class="badge badge--red">Vencida</span>
                    @else
                        <span class="badge badge--gray">{{ ucfirst($s->estado) }}</span>
                    @endif
                </td>
                <td class="admin-table__muted">{{ $s->fechaInicio?->format('d/m/Y') ?? '—' }}</td>
                <td class="admin-table__muted">
                    <div>{{ $s->fechaVencimiento?->format('d/m/Y') ?? '—' }}</div>
                    @if($urgenciaTexto)
                        <span class="badge {{ $urgenciaBadge }} badge--sm mt-1">{{ $urgenciaTexto }}</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($s->colegio)
                    <a href="{{ route('admin.colegios.show', $s->colegio->idColegio) }}" class="a-tbl-btn" title="Ver colegio">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="admin-table__empty">
                    <i class="fa-solid fa-inbox"></i>
                    No hay suscripciones que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.pagination', ['paginator' => $suscripciones, 'etiqueta' => 'suscripciones'])
</div>

@endsection
