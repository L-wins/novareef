@extends('admin.layouts.app')

@section('titulo', 'Suscripciones')

@section('contenido')

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-bright);margin:0 0 4px;letter-spacing:-0.4px;">
            Suscripciones
        </h1>
        <p style="font-size:0.875rem;color:var(--text);margin:0;">
            Historial de suscripciones de todos los colegios. Cambiar de plan, extender o cancelar se hace desde el detalle de cada colegio.
        </p>
    </div>
</div>

{{-- Filtros --}}
<div class="admin-card" style="padding:0.875rem 1.25rem;margin-bottom:1rem;">
    <form method="GET" action="{{ route('admin.suscripciones.index') }}" data-auto-filter
          style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <select name="estado" class="admin-input" style="max-width:180px;">
            <option value="">Todos los estados</option>
            <option value="activa"     {{ request('estado') === 'activa'     ? 'selected' : '' }}>Activa</option>
            <option value="trial"      {{ request('estado') === 'trial'      ? 'selected' : '' }}>Trial</option>
            <option value="vencida"    {{ request('estado') === 'vencida'    ? 'selected' : '' }}>Vencida</option>
            <option value="suspendida" {{ request('estado') === 'suspendida' ? 'selected' : '' }}>Suspendida</option>
        </select>
        <select name="plan" class="admin-input" style="max-width:200px;">
            <option value="">Todos los planes</option>
            @foreach($planes as $p)
                <option value="{{ $p->idPlan }}" {{ (string) request('plan') === (string) $p->idPlan ? 'selected' : '' }}>
                    {{ $p->nombre }}
                </option>
            @endforeach
        </select>
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
                <th style="text-align:right;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suscripciones as $s)
            @php $planKey = strtolower($s->plan?->nombre ?? ''); @endphp
            <tr>
                <td style="font-weight:600;color:var(--text-bright);">{{ $s->colegio?->nombreColegio ?? '—' }}</td>
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
                <td style="color:var(--text);">{{ $s->fechaInicio?->format('d/m/Y') ?? '—' }}</td>
                <td style="color:var(--text);">{{ $s->fechaVencimiento?->format('d/m/Y') ?? '—' }}</td>
                <td style="text-align:right;">
                    @if($s->colegio)
                    <a href="{{ route('admin.colegios.show', $s->colegio->idColegio) }}" class="a-tbl-btn" title="Ver colegio">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:3.5rem;color:var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size:32px;margin:0 auto 0.75rem;display:block;"></i>
                    No hay suscripciones que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($suscripciones->hasPages())
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            Mostrando {{ $suscripciones->firstItem() }}–{{ $suscripciones->lastItem() }}
            de {{ $suscripciones->total() }} suscripciones
        </span>
        <div class="admin-pagination__nav">
            @if($suscripciones->onFirstPage())
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $suscripciones->previousPageUrl() }}" class="admin-pagination__btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif
            <span class="admin-pagination__pages">
                Página {{ $suscripciones->currentPage() }} de {{ $suscripciones->lastPage() }}
            </span>
            @if($suscripciones->hasMorePages())
                <a href="{{ $suscripciones->nextPageUrl() }}" class="admin-pagination__btn">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
