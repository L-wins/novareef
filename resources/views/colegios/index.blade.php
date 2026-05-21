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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                 style="width:16px;height:16px;flex-shrink:0;">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483
                         4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                      clip-rule="evenodd"/>
            </svg>
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                 style="width:16px;height:16px;">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75
                         0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
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
                            <td>
                                <span class="plan-badge plan-{{ $colegio->planColegio }}">
                                    {{ ucfirst($colegio->planColegio) }}
                                </span>
                            </td>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                             viewBox="0 0 24 24" stroke-width="1.75"
                                             stroke="currentColor" style="width:15px;height:15px;">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5
                                                     12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639
                                                     C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </a>

                                    {{-- Editar --}}
                                    <a href="{{ route('colegios.edit', $colegio->idColegio) }}"
                                       class="btn-icon btn-icon-edit" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                             viewBox="0 0 24 24" stroke-width="1.75"
                                             stroke="currentColor" style="width:15px;height:15px;">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582
                                                     16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1
                                                     1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                 viewBox="0 0 24 24" stroke-width="1.75"
                                                 stroke="currentColor" style="width:15px;height:15px;">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty-cell">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                         viewBox="0 0 24 24" stroke-width="1.25"
                                         stroke="currentColor" class="table-empty-icon">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5
                                                 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621
                                                 .504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
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
