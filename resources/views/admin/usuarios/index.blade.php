@extends('admin.layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')

<div class="admin-page-header">
    <h1>Usuarios</h1>
    <p>Cuentas de todos los colegios registrados en la plataforma.</p>
</div>

{{-- Filtros --}}
<div class="admin-card admin-card--filters">
    <form method="GET" action="{{ route('admin.usuarios.index') }}" data-auto-filter class="admin-filters">
        <div class="admin-search-bar">
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
        <div class="admin-filter admin-filter--wide">
            <select name="colegio" data-nova-select data-searchable="true" data-placeholder="Todos los colegios">
                <option value="">Todos los colegios</option>
                @foreach($colegios as $c)
                    <option value="{{ $c->idColegio }}" {{ (string) request('colegio') === (string) $c->idColegio ? 'selected' : '' }}>
                        {{ $c->nombreColegio }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="admin-filter">
            <select name="rol" data-nova-select data-placeholder="Todos los roles">
                <option value="">Todos los roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" {{ request('rol') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
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
                <th>Usuario</th>
                <th>Colegio</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Último acceso</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($usuarios as $u)
            <tr>
                <td>
                    <div class="admin-table__strong">{{ $u->nombreUsuario }}</div>
                    <div class="admin-table__sub">{{ $u->emailUsuario }}</div>
                </td>
                <td class="admin-table__muted">{{ $u->colegio?->nombreColegio ?? '—' }}</td>
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
                <td class="admin-table__muted admin-table__small">
                    {{ $u->ultimoAcceso?->format('d/m/Y H:i') ?? 'Nunca' }}
                </td>
                <td>
                    <div class="admin-table__actions">
                        <form method="POST"
                              action="{{ route('admin.usuarios.toggleEstado', $u->idUsuario) }}"
                              class="form-contents"
                              data-confirm-submit
                              data-confirm-title="{{ $u->estadoUsuario === 'activo' ? 'Suspender cuenta' : 'Activar cuenta' }}"
                              data-confirm-text="¿{{ $u->estadoUsuario === 'activo' ? 'Suspender' : 'Activar' }} la cuenta de «{{ $u->nombreUsuario }}»?"
                              data-confirm-color="{{ $u->estadoUsuario === 'activo' ? '#ef4444' : '#22c55e' }}"
                              data-confirm-btn="{{ $u->estadoUsuario === 'activo' ? 'Sí, suspender' : 'Sí, activar' }}">
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
                <td colspan="6" class="admin-table__empty">
                    <i class="fa-solid fa-inbox"></i>
                    No hay usuarios que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.pagination', ['paginator' => $usuarios, 'etiqueta' => 'usuarios'])
</div>

@endsection
