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
                Define qué debe entregar cada árbitro del colegio. La revisión documental no bloquea la activación del árbitro.
            </p>
        </div>
        <div class="document-admin-hero__stat">
            <span>Requisitos activos</span>
            <strong>{{ $requisitos->where('activo', true)->count() }}</strong>
        </div>
    </section>

    <form method="POST"
          action="{{ route('requisitos-documentos-arbitro.store') }}"
          enctype="multipart/form-data"
          class="form-card document-admin-create">
        @csrf
        <div class="form-section">
            <p class="form-section-title">Nuevo requisito</p>
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
                    <label for="orden" class="form-label">Orden</label>
                    <input type="number"
                           id="orden"
                           name="orden"
                           value="{{ old('orden', $requisitos->count() + 1) }}"
                           min="0"
                           max="999"
                           class="form-input {{ $errors->has('orden') ? 'is-invalid' : '' }}">
                </div>

                <div class="form-group span-2">
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

                <div class="document-admin-switches">
                    <input type="hidden" name="obligatorio" value="0">
                    <label class="form-check">
                        <input type="checkbox" name="obligatorio" value="1" class="form-check-input" checked>
                        <span class="form-check-label">Obligatorio</span>
                    </label>

                    <input type="hidden" name="requiereRevision" value="0">
                    <label class="form-check">
                        <input type="checkbox" name="requiereRevision" value="1" class="form-check-input" checked>
                        <span class="form-check-label">Requiere revisión</span>
                    </label>

                    <input type="hidden" name="activo" value="1">
                </div>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Crear requisito
            </button>
        </div>
    </form>

    <section class="document-config-list">
        <div class="detail-card-head">
            <span class="detail-card-icon"><i class="fa-solid fa-folder-tree"></i></span>
            <p class="detail-section-title">Requisitos configurados</p>
        </div>

        @if ($requisitos->isEmpty())
            <p class="detail-empty">Aún no hay documentos configurados para el colegio.</p>
        @else
            <div class="document-config-items">
                @foreach ($requisitos as $requisito)
                    <article class="document-config-row {{ $requisito->activo ? '' : 'is-paused' }}">
                        <form method="POST"
                              action="{{ route('requisitos-documentos-arbitro.update', $requisito->idRequisito) }}"
                              enctype="multipart/form-data"
                              class="document-config-form">
                            @csrf
                            @method('PUT')

                            <div class="document-config-row__status">
                                <span class="badge {{ $requisito->activo ? 'badge-green' : 'badge-gray' }}">
                                    {{ $requisito->activo ? 'Activo' : 'Pausado' }}
                                </span>
                                <span class="badge {{ $requisito->obligatorio ? 'badge-amber' : 'badge-gray' }}">
                                    {{ $requisito->obligatorio ? 'Obligatorio' : 'Opcional' }}
                                </span>
                            </div>

                            <div class="form-grid document-config-grid">
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
                                    <label class="form-label" for="orden-{{ $requisito->idRequisito }}">Orden</label>
                                    <input type="number"
                                           id="orden-{{ $requisito->idRequisito }}"
                                           name="orden"
                                           value="{{ old('orden', $requisito->orden) }}"
                                           min="0"
                                           max="999"
                                           class="form-input">
                                </div>

                                <div class="form-group span-2">
                                    <label class="form-label" for="descripcion-{{ $requisito->idRequisito }}">Indicaciones</label>
                                    <textarea id="descripcion-{{ $requisito->idRequisito }}"
                                              name="descripcion"
                                              rows="2"
                                              maxlength="1000"
                                              class="form-textarea">{{ old('descripcion', $requisito->descripcion) }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="plantilla-{{ $requisito->idRequisito }}">Cambiar plantilla</label>
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

                                <div class="document-admin-switches">
                                    <input type="hidden" name="obligatorio" value="0">
                                    <label class="form-check">
                                        <input type="checkbox"
                                               name="obligatorio"
                                               value="1"
                                               class="form-check-input"
                                               {{ old('obligatorio', $requisito->obligatorio) ? 'checked' : '' }}>
                                        <span class="form-check-label">Obligatorio</span>
                                    </label>

                                    <input type="hidden" name="requiereRevision" value="0">
                                    <label class="form-check">
                                        <input type="checkbox"
                                               name="requiereRevision"
                                               value="1"
                                               class="form-check-input"
                                               {{ old('requiereRevision', $requisito->requiereRevision) ? 'checked' : '' }}>
                                        <span class="form-check-label">Revisión manual</span>
                                    </label>

                                    <input type="hidden" name="activo" value="0">
                                    <label class="form-check">
                                        <input type="checkbox"
                                               name="activo"
                                               value="1"
                                               class="form-check-input"
                                               {{ old('activo', $requisito->activo) ? 'checked' : '' }}>
                                        <span class="form-check-label">Activo</span>
                                    </label>
                                </div>
                            </div>

                            <div class="document-config-row__footer">
                                <span>{{ $requisito->documentos_count }} entrega{{ $requisito->documentos_count === 1 ? '' : 's' }} asociada{{ $requisito->documentos_count === 1 ? '' : 's' }}</span>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Guardar
                                </button>
                            </div>
                        </form>

                        <form method="POST"
                              action="{{ route('requisitos-documentos-arbitro.estado', $requisito->idRequisito) }}"
                              class="document-config-toggle">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm {{ $requisito->activo ? 'btn-warning' : 'btn-primary' }}">
                                <i class="fa-solid {{ $requisito->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                {{ $requisito->activo ? 'Pausar' : 'Activar' }}
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
