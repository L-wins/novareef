{{-- Región reemplazable vía AJAX tras crear/editar/pausar/eliminar una
     categoría — ver CategoriaArbitroController y resources/js/arbitros/arbitros.js.
     Recibe: $categorias, $abrir (idCategoria a enfocar, o null). --}}
<section class="category-config-list">
    <div class="category-config-list__head">
        <div class="detail-card-head">
            <span class="detail-card-icon"><i class="fa-solid fa-tags"></i></span>
            <p class="detail-section-title">Categorías configuradas</p>
        </div>
        <span class="category-config-list__count">{{ $categorias->count() }} total</span>
    </div>

    @if ($categorias->isEmpty())
        <p class="detail-empty">Aún no hay categorías registradas para este colegio.</p>
    @else
        <div class="category-config-items">
            @foreach ($categorias as $categoria)
                @php
                    $asignados = (int) ($categoria->arbitros_count ?? 0);
                    $totalHistorico = (int) ($categoria->arbitros_total_count ?? $asignados);
                    $archivados = max(0, $totalHistorico - $asignados);
                    $puedeEliminar = ! $categoria->esPorDefecto && $totalHistorico === 0;
                    $motivoBloqueo = $categoria->esPorDefecto
                        ? 'Las categorías base no se pueden eliminar.'
                        : 'No se puede eliminar porque tiene árbitros asignados o archivados.';
                @endphp

                <details id="categoria-{{ $categoria->idCategoria }}"
                         class="category-config-card {{ $categoria->activa ? '' : 'is-paused' }}"
                         {{ (string) $abrir === (string) $categoria->idCategoria ? 'open' : '' }}>
                    <summary>
                        <span class="category-config-card__avatar">
                            {{ strtoupper(substr($categoria->nombreCategoria, 0, 2)) }}
                        </span>

                        <span class="category-config-card__title">
                            <strong>{{ $categoria->nombreCategoria }}</strong>
                            <small>
                                {{ $categoria->descripcion ?: 'Sin descripción interna.' }}
                            </small>
                        </span>

                        <span class="category-config-card__badges">
                            <span class="badge {{ $categoria->activa ? 'badge-green' : 'badge-gray' }}">
                                {{ $categoria->activa ? 'Activa' : 'Pausada' }}
                            </span>
                            <span class="badge {{ $categoria->esPorDefecto ? 'badge-blue' : 'badge-amber' }}">
                                {{ $categoria->esPorDefecto ? 'Base' : 'Personalizada' }}
                            </span>
                        </span>

                        <span class="category-config-card__meta">
                            <span>
                                <i class="fa-solid fa-user-group"></i>
                                {{ $asignados }} árbitro{{ $asignados === 1 ? '' : 's' }}
                            </span>
                            @if ($archivados > 0)
                                <span>
                                    <i class="fa-solid fa-box-archive"></i>
                                    {{ $archivados }} archivado{{ $archivados === 1 ? '' : 's' }}
                                </span>
                            @endif
                        </span>

                        <i class="fa-solid fa-chevron-down category-collapsible__chevron"></i>
                    </summary>

                    <div class="category-config-card__body">
                        <form method="POST"
                              action="{{ route('categorias.arbitro.update', $categoria->idCategoria) }}"
                              class="category-admin-form"
                              data-ajax-form
                              data-ajax-region="resumen categorias">
                            @csrf
                            @method('PUT')

                            <div class="form-grid category-form-grid">
                                <div class="form-group">
                                    <label for="nombreCategoria-{{ $categoria->idCategoria }}" class="form-label">Nombre</label>

                                    @if ($categoria->esPorDefecto)
                                        <input type="text"
                                               id="nombreCategoria-{{ $categoria->idCategoria }}"
                                               value="{{ $categoria->nombreCategoria }}"
                                               class="form-input"
                                               disabled>
                                        <input type="hidden" name="nombreCategoria" value="{{ $categoria->nombreCategoria }}">
                                        <span class="form-hint">El nombre de las categorías base se conserva para evitar duplicados del sistema.</span>
                                    @else
                                        <input type="text"
                                               id="nombreCategoria-{{ $categoria->idCategoria }}"
                                               name="nombreCategoria"
                                               value="{{ $categoria->nombreCategoria }}"
                                               maxlength="50"
                                               class="form-input">
                                    @endif
                                </div>

                                <div class="form-group category-form-grid__wide">
                                    <label for="descripcion-{{ $categoria->idCategoria }}" class="form-label">Descripción interna</label>
                                    <textarea id="descripcion-{{ $categoria->idCategoria }}"
                                              name="descripcion"
                                              rows="3"
                                              maxlength="100"
                                              data-character-counter
                                              data-character-limit="100"
                                              class="form-textarea category-description-input">{{ $categoria->descripcion }}</textarea>
                                    <span class="category-field-footer">
                                        <span class="form-hint">Máximo 100 caracteres.</span>
                                        <span class="category-character-count" data-character-counter-output>0/100</span>
                                    </span>
                                </div>
                            </div>

                            <div class="category-admin-actions">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Guardar configuración
                                </button>
                            </div>
                        </form>

                        <div class="category-operations">
                            <div>
                                <strong>Disponibilidad</strong>
                                <p>
                                    Pausar una categoría evita nuevas asignaciones. Los árbitros ya vinculados conservan su historial.
                                </p>
                            </div>

                            <div class="category-operations__actions">
                                <form method="POST"
                                      action="{{ route('categorias.arbitro.estado', $categoria->idCategoria) }}"
                                      data-ajax-form
                                      data-ajax-region="resumen categorias"
                                      data-confirm-submit
                                      data-confirm-title="{{ $categoria->activa ? 'Pausar categoría' : 'Activar categoría' }}"
                                      data-confirm-text="{{ $categoria->activa ? 'No aparecerá para nuevas asignaciones, pero los árbitros actuales conservarán esta categoría.' : 'Volverá a estar disponible para registrar o editar árbitros.' }}"
                                      data-confirm-btn="{{ $categoria->activa ? 'Sí, pausar' : 'Sí, activar' }}"
                                      data-confirm-color="{{ $categoria->activa ? '#f59e0b' : '#10b981' }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $categoria->activa ? 'btn-warning' : 'btn-primary' }}">
                                        <i class="fa-solid {{ $categoria->activa ? 'fa-pause' : 'fa-play' }}"></i>
                                        {{ $categoria->activa ? 'Pausar' : 'Activar' }}
                                    </button>
                                </form>

                                @if ($puedeEliminar)
                                    <form method="POST"
                                          action="{{ route('categorias.arbitro.destroy', $categoria->idCategoria) }}"
                                          data-ajax-form
                                          data-ajax-region="resumen categorias"
                                          data-confirm-submit
                                          data-confirm-title="Eliminar categoría"
                                          data-confirm-text="La categoría {{ $categoria->nombreCategoria }} se eliminará definitivamente."
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
                                            title="{{ $motivoBloqueo }}">
                                        <i class="fa-solid fa-lock"></i>
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</section>
