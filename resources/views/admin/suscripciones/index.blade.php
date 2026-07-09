@extends('admin.layouts.app')

@section('titulo', 'Suscripciones')

@section('contenido')

<div class="admin-page-header">
    <h1>Suscripciones</h1>
    <p>Historial de suscripciones de todos los colegios. Cambiar de plan, extender o cancelar se hace desde el detalle de cada colegio.</p>
</div>

{{-- Filtros --}}
<div class="admin-card admin-card--filters">
    <form method="GET" action="{{ route('admin.suscripciones.index') }}" data-auto-filter class="admin-filters">
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
            @php $planKey = strtolower($s->plan?->nombre ?? ''); @endphp
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
                <td class="admin-table__muted">{{ $s->fechaVencimiento?->format('d/m/Y') ?? '—' }}</td>
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
