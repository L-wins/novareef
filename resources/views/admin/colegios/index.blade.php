@extends('admin.layouts.app')

@section('titulo', 'Colegios')

@section('contenido')

{{-- Encabezado --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-bright);margin:0 0 4px;letter-spacing:-0.4px;">
            Colegios
        </h1>
        <p style="font-size:0.875rem;color:var(--text);margin:0;">
            Gestión de colegios de árbitros registrados en la plataforma.
        </p>
    </div>
    <a href="{{ route('admin.colegios.create') }}" class="a-btn a-btn--primary" style="white-space:nowrap;">
        <i data-feather="plus"></i>
        Nuevo colegio
    </a>
</div>

{{-- Buscador --}}
<div class="admin-card" style="padding:0.875rem 1.25rem;margin-bottom:1rem;">
    <form method="GET" action="{{ route('admin.colegios.index') }}">
        <div class="admin-search-bar">
            <i data-feather="search"></i>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por nombre o código…"
                   class="admin-search-input"
                   autocomplete="off">
            @if(request('q'))
                <a href="{{ route('admin.colegios.index') }}" class="admin-search-clear" title="Limpiar">
                    <i data-feather="x"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Tabla --}}
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Colegio</th>
                <th>Ciudad</th>
                <th>Plan</th>
                <th>Estado</th>
                <th>Árbitros</th>
                <th style="text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($colegios as $colegio)
            @php
                $planNombre = strtolower($colegio->suscripcionActiva?->plan?->nombre ?? '');
                $planLabel  = $colegio->suscripcionActiva?->plan?->nombre;
            @endphp
            <tr>
                <td>
                    <code style="font-size:0.75rem;font-family:monospace;color:var(--text);">
                        {{ $colegio->codigoColegio }}
                    </code>
                </td>
                <td>
                    <div style="font-weight:600;color:var(--text-bright);">{{ $colegio->nombreColegio }}</div>
                    <div style="font-size:0.75rem;color:var(--text);">{{ $colegio->emailColegio }}</div>
                </td>
                <td style="color:var(--text);">{{ $colegio->ciudadColegio ?? '—' }}</td>
                <td>
                    @if($planLabel)
                        <span class="badge badge--plan-{{ $planNombre }}">{{ $planLabel }}</span>
                    @else
                        <span class="badge badge--gray">Sin plan</span>
                    @endif
                </td>
                <td>
                    @if($colegio->estadoColegio === 'activo')
                        <span class="badge badge--green">Activo</span>
                    @elseif($colegio->estadoColegio === 'suspendido')
                        <span class="badge badge--red">Suspendido</span>
                    @elseif($colegio->estadoColegio === 'trial')
                        <span class="badge badge--amber">Trial</span>
                    @else
                        <span class="badge badge--gray">{{ ucfirst($colegio->estadoColegio) }}</span>
                    @endif
                </td>
                <td>
                    <span style="font-weight:600;color:var(--text-bright);">{{ $colegio->arbitros_count }}</span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                        <a href="{{ route('admin.colegios.show', $colegio->idColegio) }}"
                           class="a-tbl-btn" title="Ver detalle">
                            <i data-feather="eye"></i>
                        </a>
                        <a href="{{ route('admin.colegios.edit', $colegio->idColegio) }}"
                           class="a-tbl-btn" title="Editar">
                            <i data-feather="edit-2"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('admin.colegios.toggleEstado', $colegio->idColegio) }}"
                              style="display:contents;"
                              onsubmit="return confirm('¿Cambiar estado del colegio «{{ addslashes($colegio->nombreColegio) }}»?')">
                            @csrf
                            @method('PUT')
                            <button type="submit"
                                    class="a-tbl-btn {{ $colegio->estadoColegio === 'activo' ? 'a-tbl-btn--danger' : 'a-tbl-btn--success' }}"
                                    title="{{ $colegio->estadoColegio === 'activo' ? 'Suspender' : 'Activar' }}">
                                <i data-feather="power"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:3.5rem;color:var(--text-muted);">
                    <div>
                        <i data-feather="inbox" style="width:32px;height:32px;margin:0 auto 0.75rem;display:block;"></i>
                        @if(request('q'))
                            No se encontraron colegios para <strong style="color:var(--text);">«{{ request('q') }}»</strong>
                        @else
                            No hay colegios registrados aún.
                            <br>
                            <a href="{{ route('admin.colegios.create') }}"
                               style="color:var(--primary);font-size:0.8125rem;margin-top:0.5rem;display:inline-block;">
                                Registrar el primer colegio
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Paginación --}}
    @if($colegios->hasPages())
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            Mostrando {{ $colegios->firstItem() }}–{{ $colegios->lastItem() }}
            de {{ $colegios->total() }} colegios
        </span>
        <div class="admin-pagination__nav">
            @if($colegios->onFirstPage())
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i data-feather="chevron-left"></i>
                </span>
            @else
                <a href="{{ $colegios->previousPageUrl() }}" class="admin-pagination__btn">
                    <i data-feather="chevron-left"></i>
                </a>
            @endif
            <span class="admin-pagination__pages">
                Página {{ $colegios->currentPage() }} de {{ $colegios->lastPage() }}
            </span>
            @if($colegios->hasMorePages())
                <a href="{{ $colegios->nextPageUrl() }}" class="admin-pagination__btn">
                    <i data-feather="chevron-right"></i>
                </a>
            @else
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i data-feather="chevron-right"></i>
                </span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
