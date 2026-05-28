@extends('layouts.app')

@section('titulo', 'Categorías de árbitro')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/>
        </svg>
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
                                    <form method="POST" action="{{ route('categorias.arbitro.toggleActiva', $categoria->idCategoria) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-secondary btn-sm"
                                                title="{{ $categoria->activa ? 'Desactivar' : 'Activar' }}">
                                            @if($categoria->activa)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;">
                                                    <path d="M6.75 9.25a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Z"/>
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-8-8a8 8 0 1 1 16 0A8 8 0 0 1 2 10Z" clip-rule="evenodd"/>
                                                </svg>
                                                Desactivar
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                                </svg>
                                                Activar
                                            @endif
                                        </button>
                                    </form>

                                    {{-- Eliminar (solo si no es por defecto) --}}
                                    @if(!$categoria->esPorDefecto)
                                        <form method="POST" action="{{ route('categorias.arbitro.destroy', $categoria->idCategoria) }}"
                                              onsubmit="return confirm('¿Eliminar la categoría «{{ $categoria->nombreCategoria }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;">
                                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                                </svg>
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
        <div class="form-section" style="background:rgba(16,185,129,.03);border-bottom:none;">
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                        </svg>
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
