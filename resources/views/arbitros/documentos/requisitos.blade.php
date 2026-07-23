@extends('layouts.app')

@section('titulo', 'Documentos de árbitros')
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container arbitros-page document-admin-page">
    @if (session('success'))
        <div id="flash-msg" class="flash-success">{{ session('success') }}</div>
    @elseif (session('error'))
        <div id="flash-msg" class="flash-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash-error">
            <strong>Corrige los siguientes errores:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="profile-topbar">
        <a href="{{ route('arbitros.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a árbitros
        </a>
    </div>

    <section class="page-hero document-admin-hero">
        <div>
            <span class="page-kicker">Expediente arbitral</span>
            <h1 class="page-heading">Documentos solicitados</h1>
            <p class="page-subtitle">
                Configura documentos globales o exclusivos por categoría. La revisión documental no bloquea la activación del árbitro.
            </p>
        </div>
        <div class="document-admin-hero__stats">
            <div>
                <span>Activos</span>
                <strong>{{ $requisitos->where('activo', true)->count() }}</strong>
            </div>
            <div>
                <span>Por categoría</span>
                <strong>{{ $requisitos->whereNotNull('idCategoria')->count() }}</strong>
            </div>
            <div>
                <span>Plantillas</span>
                <strong>{{ $requisitos->whereNotNull('plantillaRuta')->count() }}</strong>
            </div>
        </div>
    </section>

    <details class="document-collapsible document-collapsible--create" {{ $errors->any() ? 'open' : '' }}>
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
              class="document-admin-form">
            @csrf
            <div class="form-grid document-admin-form-grid">
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del documento <span class="req">*</span></label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           value="{{ old('nombre') }}"
                           maxlength="120"
                           placeholder="Ej. Hoja de vida actualizada"
                           class="form-input {{ $errors->has('nombre') ? 'is-invalid' : '' }}">
                </div>

                <div class="form-group">
                    <label for="idCategoria" class="form-label">Aplica a</label>
                    <select id="idCategoria"
                            name="idCategoria"
                            class="form-select {{ $errors->has('idCategoria') ? 'is-invalid' : '' }}">
                        <option value="">Todos los árbitros</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->idCategoria }}" {{ (string) old('idCategoria') === (string) $categoria->idCategoria ? 'selected' : '' }}>
                                {{ $categoria->nombreCategoria }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="orden" class="form-label">Orden</label>
                    <input type="number"
                           id="orden"
                           name="orden"
                           value="{{ old('orden', $requisitos->count() + 1) }}"
                           min="0"
                           max="999"
                           class="form-input {{ $errors->has('orden') ? 'is-invalid' : '' }}">
                </div>

                <div class="form-group document-admin-form__wide">
                    <label for="descripcion" class="form-label">Indicaciones para el árbitro</label>
                    <textarea id="descripcion"
                              name="descripcion"
                              rows="3"
                              maxlength="1000"
                              placeholder="Explica qué debe adjuntar o cómo debe diligenciar la plantilla."
                              class="form-textarea {{ $errors->has('descripcion') ? 'is-invalid' : '' }}">{{ old('descripcion') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="plantilla" class="form-label">Plantilla descargable</label>
                    <input type="file"
                           id="plantilla"
                           name="plantilla"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="form-input {{ $errors->has('plantilla') ? 'is-invalid' : '' }}">
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

    <section class="document-config-list">
        <div class="document-config-list__head">
            <div class="detail-card-head">
                <span class="detail-card-icon"><i class="fa-solid fa-folder-tree"></i></span>
                <p class="detail-section-title">Requisitos configurados</p>
            </div>
            <span class="document-config-list__count">{{ $requisitos->count() }} total</span>
        </div>

        @if ($requisitos->isEmpty())
            <p class="detail-empty">Aún no hay documentos configurados para el colegio.</p>
        @else
            <div class="document-config-items">
                @foreach ($requisitos as $requisito)
                    @php
                        $alcance = $requisito->categoria?->nombreCategoria ?? 'Todos los árbitros';
                    @endphp

                    <details class="document-config-card {{ $requisito->activo ? '' : 'is-paused' }}">
                        <summary>
                            <span class="document-config-card__drag">{{ str_pad((string) $requisito->orden, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="document-config-card__title">
                                <strong>{{ $requisito->nombre }}</strong>
                                <small>
                                    <i class="fa-solid fa-users-viewfinder"></i>
                                    {{ $alcance }}
                                </small>
                            </span>
                            <span class="document-config-card__badges">
                                <span class="badge {{ $requisito->activo ? 'badge-green' : 'badge-gray' }}">
                                    {{ $requisito->activo ? 'Activo' : 'Pausado' }}
                                </span>
                                <span class="badge {{ $requisito->obligatorio ? 'badge-amber' : 'badge-gray' }}">
                                    {{ $requisito->obligatorio ? 'Obligatorio' : 'Opcional' }}
                                </span>
                                <span class="badge {{ $requisito->requiereRevision ? 'badge-blue' : 'badge-green' }}">
                                    {{ $requisito->requiereRevision ? 'Revisión' : 'Auto-aprueba' }}
                                </span>
                            </span>
                            <span class="document-config-card__meta">
                                <span>{{ $requisito->documentos_count }} entrega{{ $requisito->documentos_count === 1 ? '' : 's' }}</span>
                                @if ($requisito->plantillaRuta)
                                    <span><i class="fa-solid fa-file-arrow-down"></i> Plantilla</span>
                                @else
                                    <span><i class="fa-solid fa-file-circle-question"></i> Sin plantilla</span>
                                @endif
                            </span>
                            <i class="fa-solid fa-chevron-down document-collapsible__chevron"></i>
                        </summary>

                        <div class="document-config-card__body">
                            <form method="POST"
                                  action="{{ route('requisitos-documentos-arbitro.update', $requisito->idRequisito) }}"
                                  enctype="multipart/form-data"
                                  class="document-admin-form">
                                @csrf
                                @method('PUT')

                                <div class="form-grid document-admin-form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="nombre-{{ $requisito->idRequisito }}">Nombre</label>
                                        <input type="text"
                                               id="nombre-{{ $requisito->idRequisito }}"
                                               name="nombre"
                                               value="{{ old('nombre', $requisito->nombre) }}"
                                               maxlength="120"
                                               class="form-input">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="idCategoria-{{ $requisito->idRequisito }}">Aplica a</label>
                                        <select id="idCategoria-{{ $requisito->idRequisito }}"
                                                name="idCategoria"
                                                class="form-select">
                                            <option value="">Todos los árbitros</option>
                                            @foreach ($categorias as $categoria)
                                                <option value="{{ $categoria->idCategoria }}" {{ (string) old('idCategoria', $requisito->idCategoria) === (string) $categoria->idCategoria ? 'selected' : '' }}>
                                                    {{ $categoria->nombreCategoria }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="orden-{{ $requisito->idRequisito }}">Orden</label>
                                        <input type="number"
                                               id="orden-{{ $requisito->idRequisito }}"
                                               name="orden"
                                               value="{{ old('orden', $requisito->orden) }}"
                                               min="0"
                                               max="999"
                                               class="form-input">
                                    </div>

                                    <div class="form-group document-admin-form__wide">
                                        <label class="form-label" for="descripcion-{{ $requisito->idRequisito }}">Indicaciones</label>
                                        <textarea id="descripcion-{{ $requisito->idRequisito }}"
                                                  name="descripcion"
                                                  rows="3"
                                                  maxlength="1000"
                                                  class="form-textarea">{{ old('descripcion', $requisito->descripcion) }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="plantilla-{{ $requisito->idRequisito }}">Plantilla</label>
                                        <input type="file"
                                               id="plantilla-{{ $requisito->idRequisito }}"
                                               name="plantilla"
                                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                               class="form-input">
                                        @if ($requisito->plantillaRuta)
                                            <a href="{{ route('documentos.arbitro.plantilla', $requisito->idRequisito) }}" class="document-link">
                                                <i class="fa-solid fa-download"></i>
                                                {{ $requisito->plantillaNombreOriginal ?? 'Plantilla actual' }}
                                            </a>
                                        @else
                                            <span class="form-hint">Sin plantilla cargada.</span>
                                        @endif
                                    </div>

                                    <div class="document-admin-switches document-admin-switches--inline">
                                        <input type="hidden" name="obligatorio" value="0">
                                        <label class="document-toggle">
                                            <input type="checkbox"
                                                   name="obligatorio"
                                                   value="1"
                                                   {{ old('obligatorio', $requisito->obligatorio) ? 'checked' : '' }}>
                                            <span>Obligatorio</span>
                                        </label>

                                        <input type="hidden" name="requiereRevision" value="0">
                                        <label class="document-toggle">
                                            <input type="checkbox"
                                                   name="requiereRevision"
                                                   value="1"
                                                   {{ old('requiereRevision', $requisito->requiereRevision) ? 'checked' : '' }}>
                                            <span>Revisión manual</span>
                                        </label>

                                        <input type="hidden" name="activo" value="0">
                                        <label class="document-toggle">
                                            <input type="checkbox"
                                                   name="activo"
                                                   value="1"
                                                   {{ old('activo', $requisito->activo) ? 'checked' : '' }}>
                                            <span>Activo</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="document-admin-actions">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Guardar configuración
                                    </button>
                                </div>
                            </form>

                            <form method="POST"
                                  action="{{ route('requisitos-documentos-arbitro.estado', $requisito->idRequisito) }}"
                                  class="document-config-card__state-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm {{ $requisito->activo ? 'btn-warning' : 'btn-primary' }}">
                                    <i class="fa-solid {{ $requisito->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                    {{ $requisito->activo ? 'Pausar requisito' : 'Activar requisito' }}
                                </button>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
