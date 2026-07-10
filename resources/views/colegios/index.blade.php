@extends('layouts.app')

@section('titulo', 'Colegios')
@section('seccion', 'Colegios')

@push('styles')
    @vite(['resources/css/colegios/colegios.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Flash --}}
    @if (session('success'))
        <div class="flash flash-success" id="flash-msg">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Cabecera --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Colegios</h1>
            <p class="page-subheading">Gestión de colegios de árbitros registrados en la plataforma</p>
        </div>
        <a href="{{ route('colegios.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Nuevo Colegio
        </a>
    </div>

    {{-- Tabla --}}
    <div class="table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre del colegio</th>
                        <th>Ciudad</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($colegios as $colegio)
                        <tr>
                            <td class="td-code">{{ $colegio->codigoColegio }}</td>
                            <td class="td-primary">{{ $colegio->nombreColegio }}</td>
                            <td>{{ $colegio->ciudadColegio ?? '—' }}</td>
                            <td>{{ $colegio->suscripcionActiva?->plan?->nombre ?? '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ $colegio->estadoColegio }}">
                                    {{ ucfirst($colegio->estadoColegio) }}
                                </span>
                            </td>
                            <td class="td-actions">
                                <div class="table-actions">

                                    {{-- Ver --}}
                                    <a href="{{ route('colegios.show', $colegio->idColegio) }}"
                                       class="btn-icon btn-icon-view" title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    {{-- Editar --}}
                                    <a href="{{ route('colegios.edit', $colegio->idColegio) }}"
                                       class="btn-icon btn-icon-edit" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    {{-- Toggle estado --}}
                                    <form method="POST"
                                          action="{{ route('colegios.toggleEstado', $colegio->idColegio) }}"
                                          style="display:contents;">
                                        @csrf
                                        @method('PUT')
                                        @php
                                            $esSuspender = $colegio->estadoColegio === 'activo';
                                        @endphp
                                        <button type="submit"
                                                class="btn-icon {{ $esSuspender ? 'btn-icon-suspend' : 'btn-icon-activate' }}"
                                                title="{{ $esSuspender ? 'Suspender' : 'Activar' }}"
                                                data-confirm="{{ $esSuspender
                                                    ? '¿Suspender el colegio «' . $colegio->nombreColegio . '»?'
                                                    : '¿Activar el colegio «' . $colegio->nombreColegio . '»?' }}">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty-cell">
                                <div>
                                    <i class="fa-solid fa-building-columns table-empty-icon"></i>
                                    <p class="table-empty-title">No hay colegios registrados</p>
                                    <p class="table-empty-text">Crea el primer colegio para comenzar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/colegios/colegios.js'])
@endpush
