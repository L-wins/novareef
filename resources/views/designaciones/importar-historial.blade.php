@extends('layouts.app')

@section('titulo', 'Historial de importaciones')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="{{ route('designaciones.importar.mostrar') }}">Importar desde Word</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Historial</span>
    </div>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Historial de importaciones</h1>
            <p class="page-subheading">Auditoría de cada archivo .docx importado: quién, cuándo, y cuántos partidos generó.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Torneo</th>
                    <th>Importado por</th>
                    <th>Fecha</th>
                    <th>Partidos</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($importaciones as $importacion)
                @php
                    $estadoLabel = [
                        'procesando' => ['En revisión', 'warn'],
                        'confirmada' => ['Confirmada', 'ok'],
                        'revertida'  => ['Revertida', 'error'],
                        'cancelada'  => ['Cancelada', 'error'],
                    ][$importacion->estado] ?? [$importacion->estado, 'ok'];
                @endphp
                <tr>
                    <td><i class="fa-solid fa-file-word"></i> {{ $importacion->nombreArchivoOriginal }}</td>
                    <td>{{ $importacion->torneo?->nombreTorneo ?? '—' }}</td>
                    <td>{{ $importacion->usuario?->nombreUsuario ?? '—' }}</td>
                    <td>{{ $importacion->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $importacion->totalCreados }} / {{ $importacion->totalFilas }}</td>
                    <td>
                        <span class="badge-fila-estado badge-fila-estado--{{ $estadoLabel[1] }}">
                            {{ $estadoLabel[0] }}
                        </span>
                        @if($importacion->estado === 'revertida' && $importacion->usuarioReversion)
                            <p class="field-hint">por {{ $importacion->usuarioReversion->nombreUsuario }}</p>
                        @endif
                    </td>
                    <td>
                        @if($importacion->puedeRevertirse())
                        <form method="POST" action="{{ route('designaciones.importar.revertir', $importacion->idImportacion) }}"
                              data-confirm-submit
                              data-confirm-title="¿Revertir esta importación?"
                              data-confirm-text="Se eliminarán los {{ $importacion->totalCreados }} partido(s) que creó, solo si ninguno se ha publicado ni tiene pagos generados."
                              data-confirm-color="#ef4444"
                              data-confirm-btn="Sí, revertir">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i class="fa-solid fa-rotate-left"></i> Revertir
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fa-solid fa-file-word" style="font-size:2.5rem;color:var(--text-muted);margin-bottom:1rem"></i>
                            <p class="empty-state__title">Todavía no se ha importado ningún archivo</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($importaciones->hasPages())
    <div class="pagination-wrap">
        {{ $importaciones->links() }}
    </div>
    @endif

</div>
@endsection
