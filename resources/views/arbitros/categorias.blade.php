@extends('layouts.app')

@section('titulo', 'Categorías de árbitro')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a árbitros
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Categorías de árbitro</h1>
            <p class="page-subheading">Gestiona las categorías disponibles para asignar a los árbitros de tu colegio.</p>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div class="form-card" style="margin-bottom:2rem;">

        {{-- Tabla de categorías --}}
        <div class="form-section" style="padding-bottom:0;">
            <p class="form-section-title">Categorías registradas</p>
        </div>

        @if($categorias->isEmpty())
            <div style="padding:2rem 1.5rem;text-align:center;color:var(--text-muted);font-size:0.875rem;">
                No hay categorías registradas aún.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $categoria)
                        <tr>
                            <td style="font-weight:500;">{{ $categoria->nombreCategoria }}</td>
                            <td>
                                @if($categoria->esPorDefecto)
                                    <span class="badge badge-blue">Por defecto</span>
                                @else
                                    <span class="badge badge-gray">Personalizada</span>
                                @endif
                            </td>
                            <td>
                                @if($categoria->activa)
                                    <span class="badge badge-green">Activa</span>
                                @else
                                    <span class="badge badge-red">Inactiva</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:0.5rem;justify-content:flex-end;align-items:center;">

                                    {{-- Toggle activa --}}
                                    <form method="POST" action="{{ route('categorias.arbitro.estado', $categoria->idCategoria) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-secondary btn-sm"
                                                title="{{ $categoria->activa ? 'Desactivar' : 'Activar' }}">
                                            <i class="fa-solid {{ $categoria->activa ? 'fa-circle-minus' : 'fa-circle-check' }}"></i>
                                            {{ $categoria->activa ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>

                                    {{-- Eliminar (solo si no es por defecto) --}}
                                    @if(!$categoria->esPorDefecto)
                                        <form method="POST" action="{{ route('categorias.arbitro.destroy', $categoria->idCategoria) }}"
                                              onsubmit="return confirm('¿Eliminar la categoría «{{ $categoria->nombreCategoria }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                                Eliminar
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
        @endif

        {{-- Formulario de nueva categoría --}}
        <div class="form-section" style="background:rgba(79,142,247,.03);border-bottom:none;">
            <p class="form-section-title">Agregar categoría</p>
            <form method="POST" action="{{ route('categorias.arbitro.store') }}" novalidate>
                @csrf
                <div style="display:flex;gap:0.75rem;align-items:flex-start;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:220px;margin:0;">
                        <input type="text" id="nombreCategoria" name="nombreCategoria"
                               value="{{ old('nombreCategoria') }}"
                               maxlength="50" placeholder="Ej. B-FEM"
                               class="form-input {{ $errors->has('nombreCategoria') ? 'is-invalid' : '' }}"
                               autofocus>
                        @error('nombreCategoria')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap;margin-top:0;">
                        <i class="fa-solid fa-plus"></i>
                        Agregar
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
