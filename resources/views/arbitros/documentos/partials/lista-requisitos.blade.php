{{-- Región reemplazable vía AJAX tras crear/editar/pausar/eliminar un
     requisito — ver RequisitoDocumentoArbitroController y
     resources/js/arbitros/arbitros.js.
     Recibe: $requisitos, $categorias, $arbitros, $abrir (idRequisito a enfocar, o null). --}}
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
                    $alcance = $requisito->idArbitro
                        ? ($requisito->arbitro?->usuario?->nombreUsuario ?? 'Árbitro eliminado')
                        : ($requisito->categoria?->nombreCategoria ?? 'Todos los árbitros');
                    $puedeEliminar = $requisito->documentos_count === 0;
                @endphp

                <details id="requisito-{{ $requisito->idRequisito }}"
                         class="document-config-card {{ $requisito->activo ? '' : 'is-paused' }}"
                         {{ (string) $abrir === (string) $requisito->idRequisito ? 'open' : '' }}>
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
                              class="document-admin-form"
                              data-ajax-form
                              data-ajax-region="resumen requisitos">
                            @csrf
                            @method('PUT')

                            <div class="form-grid document-admin-form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="nombre-{{ $requisito->idRequisito }}">Nombre</label>
                                    <input type="text"
                                           id="nombre-{{ $requisito->idRequisito }}"
                                           name="nombre"
                                           value="{{ $requisito->nombre }}"
                                           maxlength="120"
                                           class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="alcanceRequisito-{{ $requisito->idRequisito }}">Aplica a</label>
                                    <select id="alcanceRequisito-{{ $requisito->idRequisito }}"
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
                                                <option value="categoria:{{ $categoria->idCategoria }}" {{ (string) $requisito->idCategoria === (string) $categoria->idCategoria ? 'selected' : '' }}>
                                                    {{ $categoria->nombreCategoria }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Un árbitro específico">
                                            @foreach ($arbitros as $arbitroOpcion)
                                                <option value="arbitro:{{ $arbitroOpcion->idArbitro }}" {{ (string) $requisito->idArbitro === (string) $arbitroOpcion->idArbitro ? 'selected' : '' }}>
                                                    {{ $arbitroOpcion->usuario?->nombreUsuario }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="orden-{{ $requisito->idRequisito }}">Orden</label>
                                    <input type="number"
                                           id="orden-{{ $requisito->idRequisito }}"
                                           name="orden"
                                           value="{{ $requisito->orden }}"
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
                                              class="form-textarea">{{ $requisito->descripcion }}</textarea>
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
                                               {{ $requisito->obligatorio ? 'checked' : '' }}>
                                        <span>Obligatorio</span>
                                    </label>

                                    <input type="hidden" name="requiereRevision" value="0">
                                    <label class="document-toggle">
                                        <input type="checkbox"
                                               name="requiereRevision"
                                               value="1"
                                               {{ $requisito->requiereRevision ? 'checked' : '' }}>
                                        <span>Revisión manual</span>
                                    </label>

                                    <input type="hidden" name="activo" value="0">
                                    <label class="document-toggle">
                                        <input type="checkbox"
                                               name="activo"
                                               value="1"
                                               {{ $requisito->activo ? 'checked' : '' }}>
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

                        <div class="document-config-card__actions">
                            <form method="POST"
                                  action="{{ route('requisitos-documentos-arbitro.estado', $requisito->idRequisito) }}"
                                  data-ajax-form
                                  data-ajax-region="resumen requisitos">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm {{ $requisito->activo ? 'btn-warning' : 'btn-primary' }}">
                                    <i class="fa-solid {{ $requisito->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                    {{ $requisito->activo ? 'Pausar requisito' : 'Activar requisito' }}
                                </button>
                            </form>

                            @if ($puedeEliminar)
                                <form method="POST"
                                      action="{{ route('requisitos-documentos-arbitro.destroy', $requisito->idRequisito) }}"
                                      data-ajax-form
                                      data-ajax-region="resumen requisitos"
                                      data-confirm-submit
                                      data-confirm-title="Eliminar requisito"
                                      data-confirm-text="El requisito {{ $requisito->nombre }} se eliminará definitivamente."
                                      data-confirm-btn="Sí, eliminar"
                                      data-confirm-color="#ef4444">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa-solid fa-trash"></i>
                                        Eliminar
                                    </button>
                                </form>
                            @else
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        disabled
                                        title="No se puede eliminar porque ya tiene documentos entregados — pausalo en su lugar.">
                                    <i class="fa-solid fa-lock"></i>
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</section>
