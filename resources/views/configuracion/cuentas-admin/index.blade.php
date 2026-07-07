@extends('layouts.app')

@section('titulo', 'Cuentas Admin')
@section('seccion', 'Configuración')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    @php
        $rolLabels = [
            'ejecutivo'  => 'Ejecutivo',
            'tesorero'   => 'Tesorero',
            'designador' => 'Designador',
            'sanciones'  => 'Sanciones',
            'tecnico'    => 'Técnico',
            'veedor'     => 'Veedor',
        ];
    @endphp

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Cuentas Admin</h1>
            <p class="page-subheading">
                {{ $usadas }} de {{ $limite === null ? 'ilimitadas' : $limite }} cuentas usadas
            </p>
        </div>
        <a href="{{ route('configuracion.cuentas-admin.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nueva cuenta
        </a>
    </div>

    @if ($limite !== null && $porcentaje >= 80)
        <div class="form-note form-note--warn" style="margin-bottom:1.5rem">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                Estás usando el {{ $porcentaje }}% de tu cupo de cuentas admin ({{ $usadas }}/{{ $limite }}).
                Actualiza tu plan si necesitas crear más cuentas.
            </span>
        </div>
    @endif

    @if ($cuentas->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-user-shield" style="font-size:48px;margin-bottom:1rem;opacity:.5;"></i>
            <p>No hay cuentas admin registradas todavía.</p>
            <a href="{{ route('configuracion.cuentas-admin.create') }}" class="btn btn-primary" style="margin-top:1rem;">
                Crear primera cuenta
            </a>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cuentas as $cuenta)
                    <tr>
                        <td>
                            <span class="td-primary">{{ $cuenta->nombreUsuario }}</span>
                            @if ($cuenta->emailUsuario)
                                <span class="td-secondary">{{ $cuenta->emailUsuario }}</span>
                            @endif
                        </td>
                        <td class="td-code">{{ $cuenta->usernameUsuario ?? '—' }}</td>
                        <td>
                            <span class="cat-badge">{{ $rolLabels[$cuenta->rolUsuario] ?? ucfirst($cuenta->rolUsuario) }}</span>
                        </td>
                        <td>
                            <span class="estado-pill" data-color="{{ $cuenta->estadoUsuario === 'activo' ? 'green' : 'gray' }}">
                                {{ $cuenta->estadoUsuario === 'activo' ? 'Activo' : 'Revocado' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('configuracion.cuentas-admin.edit', $cuenta->idUsuario) }}"
                                   class="btn-icon btn-icon-edit" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                @if ((int) $cuenta->idUsuario === (int) Auth::id())
                                    <span class="btn-icon" title="No puedes revocar tu propia cuenta" style="opacity:.35;cursor:not-allowed;">
                                        <i class="fa-solid fa-user-lock"></i>
                                    </span>
                                @elseif ($cuenta->estadoUsuario === 'activo')
                                    <form method="POST" action="{{ route('configuracion.cuentas-admin.revocar', $cuenta->idUsuario) }}" style="display:inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn-icon btn-icon-delete" title="Revocar acceso">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('configuracion.cuentas-admin.reactivar', $cuenta->idUsuario) }}" style="display:inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn-icon btn-icon-view" title="Reactivar acceso">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($cuentas->hasPages())
            <div class="pagination-wrapper">{{ $cuentas->links() }}</div>
        @endif
    @endif

</div>
@endsection
