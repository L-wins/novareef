@extends('layouts.app')

@section('titulo', 'Documentos de árbitros')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container arbitros-page document-admin-page">
    <div class="profile-topbar">
        <a href="{{ route('arbitros.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a árbitros
        </a>
    </div>

    <div data-ajax-region="resumen">
        @include('arbitros.documentos.partials.resumen-requisitos', ['requisitos' => $requisitos])
    </div>

    <details id="crear-requisito"
             class="document-collapsible document-collapsible--create">
        <summary>
            <span class="document-collapsible__icon"><i class="fa-solid fa-plus"></i></span>
            <span>
                <strong>Crear nuevo requisito</strong>
                <small>Define alcance, obligatoriedad, revisión y plantilla descargable.</small>
            </span>
            <i class="fa-solid fa-chevron-down document-collapsible__chevron"></i>
        </summary>

        <form method="POST"
              action="{{ route('requisitos-documentos-arbitro.store') }}"
              enctype="multipart/form-data"
              class="document-admin-form"
              data-ajax-form
              data-ajax-region="resumen requisitos"
              data-ajax-reset-on-success
              data-ajax-close-on-success="crear-requisito">
            @csrf
            <div class="form-grid document-admin-form-grid">
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del documento <span class="req">*</span></label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           maxlength="120"
                           placeholder="Ej. Hoja de vida actualizada"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label for="alcanceRequisito" class="form-label">Aplica a</label>
                    <select id="alcanceRequisito"
                            name="alcanceRequisito"
                            class="form-select"
                            data-nova-select
                            data-searchable="true"
                            data-placeholder="Todos los árbitros">
                        <optgroup label="Alcance general">
                            <option value="">Todos los árbitros</option>
                        </optgroup>
                        <optgroup label="Por categoría">
                            @foreach ($categorias as $categoria)
                                <option value="categoria:{{ $categoria->idCategoria }}">{{ $categoria->nombreCategoria }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Un árbitro específico">
                            @foreach ($arbitros as $arbitroOpcion)
                                <option value="arbitro:{{ $arbitroOpcion->idArbitro }}">{{ $arbitroOpcion->usuario?->nombreUsuario }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="number"
                           id="orden"
                           name="orden"
                           value="{{ $requisitos->count() + 1 }}"
                           min="0"
                           max="999"
                           class="form-input">
                </div>

                <div class="form-group document-admin-form__wide">
                    <label for="descripcion" class="form-label">Indicaciones para el árbitro</label>
                    <textarea id="descripcion"
                              name="descripcion"
                              rows="3"
                              maxlength="1000"
                              placeholder="Explica qué debe adjuntar o cómo debe diligenciar la plantilla."
                              class="form-textarea"></textarea>
                </div>

                <div class="form-group">
                    <label for="plantilla" class="form-label">Plantilla descargable</label>
                    <input type="file"
                           id="plantilla"
                           name="plantilla"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="form-input">
                    <span class="form-hint">PDF, Word o imagen. Máximo 10 MB.</span>
                </div>

                <div class="document-admin-switches document-admin-switches--inline">
                    <input type="hidden" name="obligatorio" value="0">
                    <label class="document-toggle">
                        <input type="checkbox" name="obligatorio" value="1" checked>
                        <span>Obligatorio</span>
                    </label>

                    <input type="hidden" name="requiereRevision" value="0">
                    <label class="document-toggle">
                        <input type="checkbox" name="requiereRevision" value="1" checked>
                        <span>Revisión manual</span>
                    </label>

                    <input type="hidden" name="activo" value="1">
                </div>
            </div>

            <div class="document-admin-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Crear requisito
                </button>
            </div>
        </form>
    </details>

    <div data-ajax-region="requisitos">
        @include('arbitros.documentos.partials.lista-requisitos', [
            'requisitos' => $requisitos,
            'categorias' => $categorias,
            'arbitros' => $arbitros,
            'abrir' => request('abrir'),
        ])
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
