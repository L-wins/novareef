@extends('admin.layouts.app')

@section('titulo', 'Colegios')

@section('contenido')

{{-- Encabezado --}}
<div class="admin-page-header admin-page-header--row">
    <div>
        <h1>Colegios</h1>
        <p>Gestión de colegios de árbitros registrados en la plataforma.</p>
    </div>
    <a href="{{ route('admin.colegios.create') }}" class="a-btn a-btn--primary">
        <i class="fa-solid fa-plus"></i>
        Nuevo colegio
    </a>
</div>

{{-- Buscador --}}
<div class="admin-card admin-card--filters">
    <form method="GET" action="{{ route('admin.colegios.index') }}" data-auto-filter>
        <div class="admin-search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por nombre o código…"
                   class="admin-search-input"
                   autocomplete="off">
            @if(request('q'))
                <a href="{{ route('admin.colegios.index') }}" class="admin-search-clear" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
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
                <th class="text-right">Acciones</th>
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
                    <code class="admin-table__mono">{{ $colegio->codigoColegio }}</code>
                </td>
                <td>
                    <div class="admin-table__strong">{{ $colegio->nombreColegio }}</div>
                    <div class="admin-table__sub">{{ $colegio->emailColegio }}</div>
                </td>
                <td class="admin-table__muted">{{ $colegio->ciudadColegio ?? '—' }}</td>
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
                    @elseif($colegio->estadoColegio === 'prueba')
                        <span class="badge badge--amber">Prueba</span>
                    @else
                        <span class="badge badge--gray">{{ ucfirst($colegio->estadoColegio) }}</span>
                    @endif
                </td>
                <td>
                    <span class="admin-table__strong">{{ $colegio->arbitros_count }}</span>
                </td>
                <td>
                    <div class="admin-table__actions">
                        <a href="{{ route('admin.colegios.show', $colegio->idColegio) }}"
                           class="a-tbl-btn" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.colegios.edit', $colegio->idColegio) }}"
                           class="a-tbl-btn" title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('admin.colegios.toggleEstado', $colegio->idColegio) }}"
                              class="form-contents"
                              data-confirm-submit
                              data-confirm-title="Cambiar estado"
                              data-confirm-text="¿Cambiar estado del colegio «{{ $colegio->nombreColegio }}»?"
                              data-confirm-color="{{ $colegio->estadoColegio === 'activo' ? '#ef4444' : '#22c55e' }}"
                              data-confirm-btn="Sí, cambiar">
                            @csrf
                            @method('PUT')
                            <button type="submit"
                                    class="a-tbl-btn {{ $colegio->estadoColegio === 'activo' ? 'a-tbl-btn--danger' : 'a-tbl-btn--success' }}"
                                    title="{{ $colegio->estadoColegio === 'activo' ? 'Suspender' : 'Activar' }}">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="admin-table__empty">
                    <div>
                        <i class="fa-solid fa-inbox"></i>
                        @if(request('q'))
                            No se encontraron colegios para <strong>«{{ request('q') }}»</strong>
                        @else
                            No hay colegios registrados aún.
                            <br>
                            <a href="{{ route('admin.colegios.create') }}">
                                Registrar el primer colegio
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.pagination', ['paginator' => $colegios, 'etiqueta' => 'colegios'])
</div>

@endsection
