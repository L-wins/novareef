@extends('layouts.app')

@section('titulo', 'Categorías de árbitro')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container arbitros-page categories-page">
    <div class="profile-topbar">
        <a href="{{ route('arbitros.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a árbitros
        </a>
    </div>

    <div data-ajax-region="resumen">
        @include('arbitros.partials.resumen-categorias', ['resumen' => $resumen])
    </div>

    <details id="crear-categoria" class="category-collapsible category-collapsible--create">
        <summary>
            <span class="category-collapsible__icon"><i class="fa-solid fa-plus"></i></span>
            <span>
                <strong>Crear nueva categoría</strong>
                <small>Agrega una categoría personalizada para este colegio.</small>
            </span>
            <i class="fa-solid fa-chevron-down category-collapsible__chevron"></i>
        </summary>

        <form method="POST"
              action="{{ route('categorias.arbitro.store') }}"
              class="category-admin-form"
              data-ajax-form
              data-ajax-region="resumen categorias"
              data-ajax-reset-on-success
              data-ajax-close-on-success="crear-categoria">
            @csrf

            <div class="form-grid category-form-grid">
                <div class="form-group">
                    <label for="nombreCategoria" class="form-label">Nombre <span class="req">*</span></label>
                    <input type="text"
                           id="nombreCategoria"
                           name="nombreCategoria"
                           maxlength="50"
                           placeholder="Ej. B-FEM"
                           class="form-input">
                </div>

                <div class="form-group category-form-grid__wide">
                    <label for="descripcion" class="form-label">Descripción interna</label>
                    <textarea id="descripcion"
                              name="descripcion"
                              rows="3"
                              maxlength="100"
                              data-character-counter
                              data-character-limit="100"
                              placeholder="Ej. Criterios de ascenso, edad deportiva o alcance de esta categoría."
                              class="form-textarea category-description-input"></textarea>
                    <span class="category-field-footer">
                        <span class="form-hint">Visible para la administración del colegio.</span>
                        <span class="category-character-count" data-character-counter-output>0/100</span>
                    </span>
                </div>
            </div>

            <div class="category-admin-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Crear categoría
                </button>
            </div>
        </form>
    </details>

    <div data-ajax-region="categorias">
        @include('arbitros.partials.lista-categorias', [
            'categorias' => $categorias,
            'abrir' => request('abrir'),
        ])
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
