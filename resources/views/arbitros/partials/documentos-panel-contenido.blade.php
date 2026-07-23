@php
    use App\Models\DocumentoArbitro;
@endphp
{{-- Región reemplazable vía AJAX tras subir/aprobar/devolver un documento —
     ver DocumentoArbitroController y resources/js/arbitros/arbitros.js.
     Recibe: $arbitro, $modoRevision, $documentosRequisitos, $documentosResumen. --}}
<div class="document-summary">
    <div class="document-summary__item">
        <span>Obligatorios</span>
        <strong>{{ $documentosResumen['aprobadosObligatorios'] }}/{{ $documentosResumen['obligatorios'] }}</strong>
    </div>
    <div class="document-summary__item">
        <span>Entregados</span>
        <strong>{{ $documentosResumen['entregados'] }}</strong>
    </div>
    <div class="document-summary__item">
        <span>En revisión</span>
        <strong>{{ $documentosResumen['pendientesRevision'] }}</strong>
    </div>
    <div class="document-summary__item">
        <span>Devueltos</span>
        <strong>{{ $documentosResumen['devueltos'] }}</strong>
    </div>
    <span class="badge {{ $documentosResumen['completo'] ? 'badge-green' : 'badge-amber' }}">
        {{ $documentosResumen['completo'] ? 'Completo' : 'Pendiente' }}
    </span>
</div>

@if ($documentosRequisitos->isEmpty())
    <p class="detail-empty">
        El colegio aún no ha configurado documentos para este expediente.
    </p>
@else
    <div class="document-requirements">
        @foreach ($documentosRequisitos as $item)
            @php
                $requisito = $item['requisito'];
                $documento = $item['documento'];
                $historial = $item['historial'];
            @endphp

            <article class="document-row" data-state="{{ $documento?->estadoRevision ?? DocumentoArbitro::ESTADO_PENDIENTE }}">
                <div class="document-row__main">
                    <div class="document-row__icon">
                        <i class="fa-solid fa-file-shield"></i>
                    </div>

                    <div class="document-row__content">
                        <div class="document-row__titleline">
                            <h3>{{ $requisito->nombre }}</h3>
                            <span class="badge badge-blue">
                                {{ $requisito->categoria?->nombreCategoria ?? 'Todos' }}
                            </span>
                            <span class="badge {{ $requisito->obligatorio ? 'badge-amber' : 'badge-gray' }}">
                                {{ $requisito->obligatorio ? 'Obligatorio' : 'Opcional' }}
                            </span>
                            @if ($documento)
                                <span class="badge badge-{{ $documento->estadoRevisionColor }}">
                                    {{ $documento->estadoRevisionLabel }}
                                </span>
                            @else
                                <span class="badge badge-gray">Sin entregar</span>
                            @endif
                        </div>

                        @if ($requisito->descripcion)
                            <p class="document-row__desc">{{ $requisito->descripcion }}</p>
                        @endif

                        <div class="document-row__meta">
                            @if ($requisito->plantillaRuta)
                                <a href="{{ route('documentos.arbitro.plantilla', $requisito->idRequisito) }}" class="document-link">
                                    <i class="fa-solid fa-download"></i>
                                    Descargar plantilla
                                </a>
                            @else
                                <span><i class="fa-solid fa-circle-info"></i> Sin plantilla</span>
                            @endif

                            @if ($documento)
                                <a href="{{ route('documentos.arbitro.descargar', $documento->idDocumento) }}" class="document-link">
                                    <i class="fa-solid fa-file-arrow-down"></i>
                                    {{ $documento->nombreOriginal ?? $documento->nombreDocumento }}
                                </a>
                                <span>v{{ $documento->version }} · {{ $documento->fechaSubida?->format('d/m/Y H:i') }} · {{ $documento->tamanoLegible }}</span>
                            @endif
                        </div>

                        @if ($documento?->estadoRevision === DocumentoArbitro::ESTADO_DEVUELTO && $documento->comentarioRevision)
                            <div class="document-feedback">
                                <strong>Corrección solicitada</strong>
                                <p>{{ $documento->comentarioRevision }}</p>
                            </div>
                        @endif

                        @if ($historial->count() > 1)
                            <details class="document-history">
                                <summary>Ver historial de entregas ({{ $historial->count() }})</summary>
                                <ul>
                                    @foreach ($historial->skip(1) as $version)
                                        <li>
                                            <a href="{{ route('documentos.arbitro.descargar', $version->idDocumento) }}">
                                                v{{ $version->version }} · {{ $version->estadoRevisionLabel }} · {{ $version->fechaSubida?->format('d/m/Y H:i') }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </div>

                <div class="document-row__actions">
                    @if ($documento?->estadoRevision === DocumentoArbitro::ESTADO_APROBADO)
                        <p class="document-approved-hint">
                            <i class="fa-solid fa-lock"></i>
                            Documento aprobado. Para reemplazarlo, el revisor debe devolverlo primero.
                        </p>
                    @else
                        <form method="POST"
                              action="{{ route('documentos.arbitro.store', [$arbitro->idArbitro, $requisito->idRequisito]) }}"
                              enctype="multipart/form-data"
                              class="document-upload-form"
                              data-ajax-form
                              data-ajax-region="documentos">
                            @csrf
                            <label for="documento-{{ $requisito->idRequisito }}" class="btn btn-secondary btn-sm document-file-trigger">
                                <i class="fa-solid fa-paperclip"></i>
                                Adjuntar
                            </label>
                            <input type="file"
                                   id="documento-{{ $requisito->idRequisito }}"
                                   name="archivo"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                   class="sr-only"
                                   data-document-file>
                            <span class="document-file-name" data-document-file-name>Ningún archivo</span>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-upload"></i>
                                Enviar
                            </button>
                        </form>
                    @endif

                    @if ($modoRevision && $documento)
                        <div class="document-review-actions">
                            @if ($documento->estadoRevision !== DocumentoArbitro::ESTADO_APROBADO)
                                <form method="POST"
                                      action="{{ route('documentos.arbitro.aprobar', $documento->idDocumento) }}"
                                      data-ajax-form
                                      data-ajax-region="documentos"
                                      data-confirm-submit
                                      data-confirm-title="Aprobar documento"
                                      data-confirm-text="El documento quedará marcado como válido en el expediente."
                                      data-confirm-color="#16a34a"
                                      data-confirm-btn="Sí, aprobar">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-check"></i>
                                        Aprobar
                                    </button>
                                </form>
                            @endif

                            @if ($documento->estadoRevision !== DocumentoArbitro::ESTADO_DEVUELTO)
                                <form method="POST"
                                      action="{{ route('documentos.arbitro.devolver', $documento->idDocumento) }}"
                                      class="document-return-form"
                                      data-ajax-form
                                      data-ajax-region="documentos">
                                    @csrf
                                    @method('PUT')
                                    <textarea name="comentarioRevision"
                                              class="form-textarea"
                                              rows="2"
                                              required
                                              maxlength="100"
                                              data-character-counter
                                              data-character-limit="100"
                                              placeholder="Qué debe corregir..."></textarea>
                                    <span class="document-character-count" data-character-counter-output>0/100</span>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Devolver
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
