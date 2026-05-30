@extends('layouts.app')

@section('titulo', 'Árbitros archivados')
@section('seccion', 'Árbitros archivados')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a árbitros activos
    </a>

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">
                <i class="fa-solid fa-box-archive" style="color:var(--text-secondary);margin-right:0.5rem;"></i>
                Árbitros archivados
            </h1>
            <p class="page-subheading">
                {{ $arbitros->total() }} árbitro{{ $arbitros->total() === 1 ? '' : 's' }}
                {{ $arbitros->total() === 1 ? 'archivado' : 'archivados' }}
            </p>
        </div>
    </div>

    @if ($arbitros->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-box-open" style="font-size:48px;margin-bottom:1rem;opacity:.5;"></i>
            <p>No hay árbitros archivados.</p>
            <a href="{{ route('arbitros.index') }}" class="btn btn-secondary" style="margin-top:1rem;">
                Ver árbitros activos
            </a>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Carné</th>
                        <th>Árbitro</th>
                        <th>Categoría</th>
                        <th>Fecha de archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($arbitros as $arbitro)
                    <tr>
                        <td class="td-code">{{ $arbitro->codigoCarnet }}</td>
                        <td>
                            <div class="cell-with-avatar">
                                @if ($arbitro->fotoPerfil)
                                    <img src="{{ asset('storage/' . $arbitro->fotoPerfil) }}"
                                         alt="{{ $arbitro->usuario->nombreUsuario }}"
                                         class="avatar avatar-sm">
                                @else
                                    <span class="avatar avatar-sm avatar-initials">
                                        {{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 1)) }}
                                    </span>
                                @endif
                                <div>
                                    <span class="td-primary">{{ $arbitro->usuario->nombreUsuario }}</span>
                                    <span class="td-secondary">{{ $arbitro->usuario->emailUsuario }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                        </td>
                        <td>
                            <span class="td-primary">{{ $arbitro->deleted_at?->format('d/m/Y') }}</span>
                            <span class="td-secondary">{{ $arbitro->deleted_at?->format('H:i') }}</span>
                        </td>
                        <td>
                            <button type="button"
                                    class="btn btn-secondary btn-sm btn-restaurar"
                                    data-id="{{ $arbitro->idArbitro }}"
                                    data-nombre="{{ $arbitro->usuario->nombreUsuario }}">
                                <i class="fa-solid fa-rotate-left"></i>
                                Restaurar
                            </button>

                            <form id="form-restaurar-{{ $arbitro->idArbitro }}"
                                  method="POST"
                                  action="{{ route('arbitros.restaurar', $arbitro->idArbitro) }}"
                                  style="display:none;">
                                @csrf
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($arbitros->hasPages())
            <div class="pagination-wrapper">{{ $arbitros->links() }}</div>
        @endif
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
