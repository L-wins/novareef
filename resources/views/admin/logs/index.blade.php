@extends('admin.layouts.app')

@section('titulo', 'Logs')

@section('contenido')

<div class="admin-page-header">
    <h1>Logs de acceso</h1>
    <p>Intentos de inicio de sesión y verificación 2FA del panel de administración.</p>
</div>

{{-- Filtros --}}
<div class="admin-card admin-card--filters">
    <form method="GET" action="{{ route('admin.logs.index') }}" data-auto-filter class="admin-filters">
        <div class="admin-search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="email" value="{{ request('email') }}"
                   placeholder="Buscar por email…"
                   class="admin-search-input"
                   autocomplete="off">
            @if(request('email'))
                <a href="{{ route('admin.logs.index', request()->except('email')) }}" class="admin-search-clear" title="Limpiar">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>
        <div class="admin-filter">
            <select name="resultado" data-nova-select data-placeholder="Todos los resultados">
                <option value="">Todos los resultados</option>
                <option value="exitoso" {{ request('resultado') === 'exitoso' ? 'selected' : '' }}>Exitosos</option>
                <option value="fallido" {{ request('resultado') === 'fallido' ? 'selected' : '' }}>Fallidos</option>
            </select>
        </div>
    </form>
</div>

<h2 class="admin-section-heading admin-section-heading--first">Accesos</h2>

{{-- Tabla --}}
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Resultado</th>
                <th>Email</th>
                <th>IP</th>
                <th>Navegador / dispositivo</th>
                <th class="text-right">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>
                    @if($log->exitoso)
                        <span class="badge badge--green"><i class="fa-solid fa-check"></i> Exitoso</span>
                    @else
                        <span class="badge badge--red"><i class="fa-solid fa-xmark"></i> Fallido</span>
                    @endif
                </td>
                <td class="admin-table__strong">{{ $log->email }}</td>
                <td>
                    <code class="admin-table__mono">{{ $log->ip }}</code>
                </td>
                <td class="admin-table__muted admin-table__small admin-table__truncate">
                    {{ $log->user_agent ?? '—' }}
                </td>
                <td class="text-right admin-table__muted admin-table__small admin-table__nowrap">
                    {{ $log->created_at?->format('d/m/Y H:i:s') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="admin-table__empty">
                    <i class="fa-solid fa-inbox"></i>
                    No hay registros que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.pagination', ['paginator' => $logs, 'etiqueta' => 'registros'])
</div>

<h2 class="admin-section-heading">Impersonaciones</h2>
<p class="admin-section-note">
    Registro de transparencia: cada vez que el superadmin entra como la cuenta ejecutivo de un colegio.
</p>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Acción</th>
                <th>Detalle</th>
                <th class="text-right">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($acciones as $accion)
            <tr>
                <td class="admin-table__strong">{{ $accion->admin?->nombre ?? '—' }}</td>
                <td><span class="badge badge--gray">{{ ucfirst(str_replace('_', ' ', $accion->accion)) }}</span></td>
                <td class="admin-table__muted admin-table__small admin-table__truncate">{{ $accion->detalle ?? '—' }}</td>
                <td class="text-right admin-table__muted admin-table__small admin-table__nowrap">
                    {{ $accion->created_at?->format('d/m/Y H:i:s') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="admin-table__empty">
                    <i class="fa-solid fa-inbox"></i>
                    Todavía no hay impersonaciones registradas.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.pagination', ['paginator' => $acciones, 'etiqueta' => 'acciones'])
</div>

@endsection
