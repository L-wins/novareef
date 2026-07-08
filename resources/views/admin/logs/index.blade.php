@extends('admin.layouts.app')

@section('titulo', 'Logs')

@section('contenido')

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-bright);margin:0 0 4px;letter-spacing:-0.4px;">
            Logs de acceso
        </h1>
        <p style="font-size:0.875rem;color:var(--text);margin:0;">
            Intentos de inicio de sesión y verificación 2FA del panel de administración.
        </p>
    </div>
</div>

{{-- Filtros --}}
<div class="admin-card" style="padding:0.875rem 1.25rem;margin-bottom:1rem;">
    <form method="GET" action="{{ route('admin.logs.index') }}" data-auto-filter
          style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <div class="admin-search-bar" style="flex:1;min-width:220px;">
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
        <select name="resultado" class="admin-input" style="max-width:180px;">
            <option value="">Todos los resultados</option>
            <option value="exitoso" {{ request('resultado') === 'exitoso' ? 'selected' : '' }}>Exitosos</option>
            <option value="fallido" {{ request('resultado') === 'fallido' ? 'selected' : '' }}>Fallidos</option>
        </select>
    </form>
</div>

<h2 style="font-size:1rem;font-weight:700;color:var(--text-bright);margin:0 0 0.75rem;">Accesos</h2>

{{-- Tabla --}}
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Resultado</th>
                <th>Email</th>
                <th>IP</th>
                <th>Navegador / dispositivo</th>
                <th style="text-align:right;">Fecha</th>
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
                <td style="color:var(--text-bright);font-weight:600;">{{ $log->email }}</td>
                <td>
                    <code style="font-size:0.75rem;font-family:monospace;color:var(--text);">{{ $log->ip }}</code>
                </td>
                <td style="color:var(--text);font-size:0.8125rem;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $log->user_agent ?? '—' }}
                </td>
                <td style="text-align:right;color:var(--text);font-size:0.8125rem;white-space:nowrap;">
                    {{ $log->created_at?->format('d/m/Y H:i:s') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:3.5rem;color:var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size:32px;margin:0 auto 0.75rem;display:block;"></i>
                    No hay registros que coincidan con el filtro.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            Mostrando {{ $logs->firstItem() }}–{{ $logs->lastItem() }}
            de {{ $logs->total() }} registros
        </span>
        <div class="admin-pagination__nav">
            @if($logs->onFirstPage())
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="admin-pagination__btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif
            <span class="admin-pagination__pages">
                Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}
            </span>
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="admin-pagination__btn">
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

<h2 style="font-size:1rem;font-weight:700;color:var(--text-bright);margin:2rem 0 0.75rem;">Impersonaciones</h2>
<p style="font-size:0.8125rem;color:var(--text-muted);margin:0 0 0.75rem;">
    Registro de transparencia: cada vez que el superadmin entra como la cuenta ejecutivo de un colegio.
</p>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Acción</th>
                <th>Detalle</th>
                <th style="text-align:right;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($acciones as $accion)
            <tr>
                <td style="color:var(--text-bright);font-weight:600;">{{ $accion->admin?->nombre ?? '—' }}</td>
                <td><span class="badge badge--gray">{{ ucfirst(str_replace('_', ' ', $accion->accion)) }}</span></td>
                <td style="color:var(--text);font-size:0.8125rem;max-width:420px;">{{ $accion->detalle ?? '—' }}</td>
                <td style="text-align:right;color:var(--text);font-size:0.8125rem;white-space:nowrap;">
                    {{ $accion->created_at?->format('d/m/Y H:i:s') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:3.5rem;color:var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size:32px;margin:0 auto 0.75rem;display:block;"></i>
                    Todavía no hay impersonaciones registradas.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($acciones->hasPages())
    <div class="admin-pagination">
        <span class="admin-pagination__info">
            Mostrando {{ $acciones->firstItem() }}–{{ $acciones->lastItem() }}
            de {{ $acciones->total() }} acciones
        </span>
        <div class="admin-pagination__nav">
            @if($acciones->onFirstPage())
                <span class="admin-pagination__btn admin-pagination__btn--disabled">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $acciones->previousPageUrl() }}" class="admin-pagination__btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif
            <span class="admin-pagination__pages">
                Página {{ $acciones->currentPage() }} de {{ $acciones->lastPage() }}
            </span>
            @if($acciones->hasMorePages())
                <a href="{{ $acciones->nextPageUrl() }}" class="admin-pagination__btn">
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
