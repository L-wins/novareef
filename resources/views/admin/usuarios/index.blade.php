@extends('admin.layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-bright);margin:0 0 4px;letter-spacing:-0.4px;">
            Usuarios
        </h1>
        <p style="font-size:0.875rem;color:var(--text);margin:0;">
            Cuentas de todos los colegios registrados en la plataforma.
        </p>
    </div>
</div>

{{-- Filtros --}}
<div class="admin-card" style="padding:0.875rem 1.25rem;margin-bottom:1rem;">
    <form method="GET" action="{{ route('admin.usuarios.index') }}" data-auto-filter
          style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <div class="admin-search-bar" style="flex:1;min-width:220px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por nombre o email…"
                   class="admin-search-input"
                   autocomplete="off">
            @if(request('q'))
                <a href="{{ route('admin.usuarios.index', request()->except('q')) }}" class="admin-search-clear" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>
        <select name="colegio" class="admin-input" style="max-width:220px;">
            <option value="">Todos los colegios</option>
            @foreach($colegios as $c)
                <option value="{{ $c->idColegio }}" {{ (string) request('colegio') === (string) $c->idColegio ? 'selected' : '' }}>
                    {{ $c->nombreColegio }}
                </option>
            @endforeach
        </select>
        <select name="rol" class="admin-input" style="max-width:180px;">
            <option value="">Todos los roles</option>
            @foreach($roles as $r)
                <option value="{{ $r }}" {{ request('rol') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Tabla --}}
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Colegio</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Último acceso</th>
                <th style="text-align:right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($usuarios as $u)
            <tr>
                <td>
                    <div style="font-weight:600;color:var(--text-bright);">{{ $u->nombreUsuario }}</div>
                    <div style="font-size:0.75rem;color:var(--text);">{{ $u->emailUsuario }}</div>
                </td>
                <td style="color:var(--text);">{{ $u->colegio?->nombreColegio ?? '—' }}</td>
                <td>
                    <span class="badge badge--gray">{{ ucfirst($u->rolUsuario) }}</span>
                </td>
                <td>
                    @if($u->estadoUsuario === 'activo')
                        <span class="badge badge--green">Activo</span>
                    @elseif($u->estadoUsuario === 'suspendido')
                        <span class="badge badge--red">Suspendido</span>
                    @else
                        <span class="badge badge--amber">{{ ucfirst($u->estadoUsuario) }}</span>
                    @endif
                </td>
                <td style="color:var(--text);font-size:0.8125rem;">
                    {{ $u->ultimoAcceso?->format('d/m/Y H:i') ?? 'Nunca' }}
                </td>
                <td>
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                        <form method="POST"
                              action="{{ route('admin.usuarios.toggleEstado', $u->idUsuario) }}"
                              style="display:contents;"
                              onsubmit="return confirm('¿{{ $u->estadoUsuario === 'activo' ? 'Suspender' : 'Activar' }} la cuenta de «{{ addslashes($u->nombreUsuario) }}»?')">
                            @csrf
                            @method('PUT')
                            <button type="submit"
                                    class="a-tbl-btn {{ $u->estadoUsuario === 'activo' ? 'a-tbl-btn--danger' : 'a-tbl-btn--success' }}"
                                    title="{{ $u->estadoUsuario === 'activo' ? 'Suspender' : 'Activar' }}">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:3.5rem;color:var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size:32px;margin:0 auto 0.75rem;display:block;"></i>
                    No hay usuarios que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($usuarios->hasPages())
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }}
            de {{ $usuarios->total() }} usuarios
        </span>
        <div class="admin-pagination__nav">
            @if($usuarios->onFirstPage())
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $usuarios->previousPageUrl() }}" class="admin-pagination__btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif
            <span class="admin-pagination__pages">
                Página {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}
            </span>
            @if($usuarios->hasMorePages())
                <a href="{{ $usuarios->nextPageUrl() }}" class="admin-pagination__btn">
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
