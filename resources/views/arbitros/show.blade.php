@extends('layouts.app')

@section('titulo', $arbitro->usuario->nombreUsuario)
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
@php
    $esPropio = (int) $arbitro->idUsuario === (int) auth()->id();
    $puedeFoto = $esPropio || auth()->user()->can('editar-arbitros');
    $porcentaje = $arbitro->porcentajePerfil;
    $colorBar = $arbitro->colorPerfil;
    $estadoObj = $arbitro->estado;
    $perfilChecklist = $arbitro->perfilChecklist();
    $perfilPendiente = collect($perfilChecklist)->where('completo', false);
    $documentosObligatorios = $documentosResumen['obligatorios'] ?? $arbitro->documentos->where('obligatorio', true)->count();
    $documentosOpcionales = max(0, ($documentosResumen['total'] ?? $arbitro->documentos->count()) - $documentosObligatorios);
@endphp

<div class="container arbitros-page">

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

    <section class="profile-hero profile-hero--detail">
        <div class="profile-hero-left">
            <div class="profile-photo-wrap">
                @if ($arbitro->fotoPerfil)
                    <img src="{{ asset('storage/' . $arbitro->fotoPerfil) }}"
                         alt="{{ $arbitro->usuario->nombreUsuario }}"
                         class="profile-photo">
                @else
                    <div class="profile-photo profile-photo-initials">
                        {{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 2)) }}
                    </div>
                @endif

                @if ($puedeFoto)
                    <form method="POST" action="{{ route('arbitros.foto.subir', $arbitro->idArbitro) }}"
                          enctype="multipart/form-data" class="profile-photo-form">
                        @csrf
                        <label for="input-foto" class="profile-photo-overlay" title="Cambiar foto">
                            <i class="fa-solid fa-camera"></i>
                            <span>Cambiar foto</span>
                        </label>
                        <input type="file" id="input-foto" name="foto"
                               accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
                               class="sr-only">
                    </form>

                    @if ($arbitro->fotoPerfil)
                        <form method="POST" action="{{ route('arbitros.foto.eliminar', $arbitro->idArbitro) }}"
                              class="profile-photo-delete"
                              onsubmit="return confirm('¿Eliminar la foto de perfil?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-delete" title="Eliminar foto" aria-label="Eliminar foto">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    @endif
                @endif
            </div>

            <div class="profile-hero-info">
                <span class="page-kicker">Ficha del árbitro</span>
                <h1 class="profile-hero-name">{{ $arbitro->usuario->nombreUsuario }}</h1>
                <div class="profile-hero-meta">
                    <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                    <span class="estado-pill" data-color="{{ $estadoObj->color ?? 'gray' }}">
                        {{ $estadoObj->etiqueta ?? ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                    </span>
                    <span class="code-chip">{{ $arbitro->codigoCarnet }}</span>
                </div>
                <div class="profile-keyline">
                    <span><i class="fa-solid fa-envelope"></i>{{ $arbitro->usuario->emailUsuario }}</span>
                    <span><i class="fa-solid fa-phone"></i>{{ $arbitro->usuario->telefonoUsuario ?? 'Sin teléfono' }}</span>
                    <span><i class="fa-solid fa-building-columns"></i>{{ $arbitro->colegio->nombreColegio }}</span>
                </div>
            </div>
        </div>

        <div class="profile-hero-actions">
            @if ($resumenFinanciero !== null)
                <a href="{{ route('finanzas.arbitro.show', $arbitro->idArbitro) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    Finanzas
                </a>
            @endif
            @can('editar-arbitros')
                <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}" class="btn btn-primary">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar
                </a>
                <button type="button" class="btn btn-warning" data-open-modal="cambio-estado">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Cambiar estado
                </button>
                <button type="button" class="btn btn-danger" data-open-modal="archivar">
                    <i class="fa-solid fa-box-archive"></i>
                    Archivar
                </button>
            @endcan
        </div>
    </section>

    <div class="profile-layout">
        <main class="profile-main">
            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-user"></i></span>
                    <p class="detail-section-title">Datos del usuario</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-field">
                        <span class="detail-label">Nombre completo</span>
                        <span class="detail-value">{{ $arbitro->usuario->nombreUsuario }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Correo electrónico</span>
                        <span class="detail-value">{{ $arbitro->usuario->emailUsuario }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Teléfono</span>
                        <span class="detail-value">{{ $arbitro->usuario->telefonoUsuario ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Colegio</span>
                        <span class="detail-value">{{ $arbitro->colegio->nombreColegio }}</span>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-id-card"></i></span>
                    <p class="detail-section-title">Identificación</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-field">
                        <span class="detail-label">Número de documento</span>
                        <span class="detail-value">{{ $arbitro->numeroDocumento }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Tipo de documento</span>
                        <span class="detail-value">{{ ucfirst($arbitro->tipoDocumento) }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Lugar de expedición</span>
                        <span class="detail-value">{{ $arbitro->lugarExpedicionCC ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Código de carné</span>
                        <span class="detail-value"><span class="code-chip">{{ $arbitro->codigoCarnet }}</span></span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Categoría</span>
                        <span class="detail-value">{{ $arbitro->categoria->nombreCategoria }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Ingreso al colegio</span>
                        <span class="detail-value">{{ $arbitro->fechaIngresoColegio ? $arbitro->fechaIngresoColegio->format('d/m/Y') : '—' }}</span>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-heart-pulse"></i></span>
                    <p class="detail-section-title">Información física y de salud</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-field">
                        <span class="detail-label">Peso</span>
                        <span class="detail-value">{{ $arbitro->pesoArbitro ? $arbitro->pesoArbitro . ' kg' : '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Estatura</span>
                        <span class="detail-value">{{ $arbitro->estaturaArbitro ? $arbitro->estaturaArbitro . ' m' : '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">RH</span>
                        <span class="detail-value">{{ $arbitro->rhArbitro ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">EPS</span>
                        <span class="detail-value">{{ $arbitro->epsArbitro ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Profesión</span>
                        <span class="detail-value">{{ $arbitro->profesionArbitro ?? '—' }}</span>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <p class="detail-section-title">Ubicación</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-field detail-field--wide">
                        <span class="detail-label">Dirección</span>
                        <span class="detail-value">{{ $arbitro->direccionArbitro ?? '—' }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Barrio</span>
                        <span class="detail-value">{{ $arbitro->barrioArbitro ?? '—' }}</span>
                    </div>
                </div>
            </section>

            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-car-side"></i></span>
                    <p class="detail-section-title">Vehículo</p>
                </div>
                @if ($arbitro->tieneVehiculo)
                    <div class="detail-grid">
                        <div class="detail-field">
                            <span class="detail-label">Tipo de vehículo</span>
                            <span class="detail-value">{{ ucfirst($arbitro->tipoVehiculo) }}</span>
                        </div>
                        <div class="detail-field">
                            <span class="detail-label">Marca</span>
                            <span class="detail-value">{{ $arbitro->marcaVehiculo ?? '—' }}</span>
                        </div>
                        <div class="detail-field">
                            <span class="detail-label">Placa</span>
                            <span class="detail-value">{{ $arbitro->placaVehiculo ?? '—' }}</span>
                        </div>
                        <div class="detail-field">
                            <span class="detail-label">Color</span>
                            <span class="detail-value">{{ $arbitro->colorVehiculo ?? '—' }}</span>
                        </div>
                    </div>
                @else
                    <p class="detail-empty">El árbitro no registra vehículo.</p>
                @endif
            </section>

            @include('arbitros.partials.documentos-panel', [
                'modoRevision' => auth()->user()->can('editar-arbitros'),
            ])

            @can('editar-arbitros')
            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-timeline"></i></span>
                    <p class="detail-section-title">Historial de estados</p>
                </div>
                @if ($arbitro->historialEstados->isEmpty())
                    <p class="detail-empty">No hay cambios de estado registrados.</p>
                @else
                    <ol class="timeline">
                        @foreach ($arbitro->historialEstados->sortByDesc('created_at') as $h)
                            <li class="timeline-item">
                                <span class="timeline-dot" data-color="{{ $h->estadoNuevoModel->color ?? 'gray' }}"></span>
                                <div class="timeline-content">
                                    <div class="timeline-head">
                                        <span class="estado-pill estado-pill-sm" data-color="{{ $h->estadoNuevoModel->color ?? 'gray' }}">
                                            {{ $h->estadoNuevoModel->etiqueta ?? $h->estadoNuevo }}
                                        </span>
                                        <span class="timeline-date">{{ $h->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="timeline-meta">
                                        De <strong>{{ ucfirst(str_replace('_', ' ', $h->estadoAnterior)) }}</strong>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <strong>{{ ucfirst(str_replace('_', ' ', $h->estadoNuevo)) }}</strong>
                                        por <strong>{{ $h->usuarioCambio?->nombreUsuario ?? 'Sistema' }}</strong>
                                    </p>
                                    @if ($h->fechaInicio || $h->fechaFin)
                                        <p class="timeline-meta">
                                            @if ($h->fechaInicio)
                                                <span class="detail-label">Inicio:</span>
                                                {{ $h->fechaInicio->format('d/m/Y') }}
                                            @endif
                                            @if ($h->fechaFin)
                                                <span class="detail-label">Fin:</span>
                                                {{ $h->fechaFin->format('d/m/Y') }}
                                            @endif
                                        </p>
                                    @endif
                                    @if ($h->motivo)
                                        <p class="timeline-motivo">{{ $h->motivo }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
            @endcan
        </main>

        <aside class="profile-side">
            <section class="detail-card profile-summary-card">
                <div class="profile-progress profile-progress--large">
                    <div class="profile-progress-head">
                        <span class="profile-progress-label">Perfil completo</span>
                        <span class="profile-progress-value" data-color="{{ $colorBar }}">{{ $porcentaje }}%</span>
                    </div>
                    <div class="profile-progress-bar">
                        <div class="profile-progress-fill" data-color="{{ $colorBar }}"
                             style="width: {{ $porcentaje }}%;"></div>
                    </div>
                </div>

                <div class="profile-checklist">
                    @foreach ($perfilChecklist as $item)
                        <div class="profile-checkitem {{ $item['completo'] ? 'is-complete' : '' }}">
                            <span class="profile-checkitem__icon">
                                <i class="fa-solid {{ $item['icono'] }}"></i>
                            </span>
                            <div>
                                <span class="profile-checkitem__title">{{ $item['etiqueta'] }}</span>
                                <span class="profile-checkitem__desc">{{ $item['descripcion'] }}</span>
                            </div>
                            <i class="fa-solid {{ $item['completo'] ? 'fa-check' : 'fa-circle' }} profile-checkitem__state"></i>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($resumenFinanciero !== null)
                <section class="detail-card">
                    <div class="detail-card-head">
                        <span class="detail-card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <p class="detail-section-title">Estado financiero</p>
                    </div>
                    <div class="finance-mini-grid">
                        <div>
                            <span class="detail-label">Le debemos</span>
                            <strong class="monto-negativo">${{ number_format($resumenFinanciero['leDebemos'], 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span class="detail-label">Nos debe</span>
                            <strong class="monto-positivo">${{ number_format($resumenFinanciero['nosDebe'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    <a href="{{ route('finanzas.arbitro.show', $arbitro->idArbitro) }}" class="btn btn-secondary btn-block">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        Abrir ficha financiera
                    </a>
                </section>
            @endif

            <section class="detail-card">
                <div class="detail-card-head">
                    <span class="detail-card-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <p class="detail-section-title">Estado operativo</p>
                </div>
                <div class="operational-list">
                    <div>
                        <span class="detail-label">Designable</span>
                        @if ($arbitro->puedeSerDesignado())
                            <span class="badge badge-green">Sí</span>
                        @else
                            <span class="badge badge-amber">No</span>
                        @endif
                    </div>
                    <div>
                        <span class="detail-label">Estado documental</span>
                        <span class="badge {{ ($documentosResumen['completo'] ?? true) ? 'badge-green' : 'badge-amber' }}">
                            {{ ($documentosResumen['completo'] ?? true) ? 'Completo' : 'Pendiente' }}
                        </span>
                    </div>
                    <div>
                        <span class="detail-label">Obligatorios aprobados</span>
                        <span class="detail-value">{{ $documentosResumen['aprobadosObligatorios'] ?? 0 }}/{{ $documentosObligatorios }}</span>
                    </div>
                    <div>
                        <span class="detail-label">En revisión</span>
                        <span class="detail-value">{{ $documentosResumen['pendientesRevision'] ?? 0 }}</span>
                    </div>
                    <div>
                        <span class="detail-label">Devueltos</span>
                        <span class="detail-value">{{ $documentosResumen['devueltos'] ?? 0 }}</span>
                    </div>
                    <div>
                        <span class="detail-label">Puntos pendientes</span>
                        <span class="detail-value">{{ $perfilPendiente->sum('puntos') }}</span>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>

@can('editar-arbitros')
<div class="modal" id="modal-cambio-estado" role="dialog" aria-modal="true" aria-labelledby="modal-cambio-estado-title">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" action="{{ route('arbitros.estado', $arbitro->idArbitro) }}"
              id="form-cambio-estado"
              data-confirm-nombre="{{ $arbitro->usuario->nombreUsuario }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title" id="modal-cambio-estado-title">Cambiar estado del árbitro</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="estadoNuevo" class="form-label">Nuevo estado <span class="req">*</span></label>
                    <select id="estadoNuevo" name="estadoNuevo"
                            class="form-select {{ $errors->has('estadoNuevo') ? 'is-invalid' : '' }}">
                        <option value="">Selecciona un estado</option>
                        @foreach ($estados as $est)
                            @if ($est->nombre !== $arbitro->estadoArbitro)
                                <option value="{{ $est->nombre }}" {{ old('estadoNuevo') === $est->nombre ? 'selected' : '' }}>
                                    {{ $est->etiqueta }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="fechas-wrap" hidden>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="fechaInicio" class="form-label">Fecha de inicio <span class="req">*</span></label>
                            <input type="date" id="fechaInicio" name="fechaInicio"
                                   value="{{ old('fechaInicio') }}"
                                   class="form-input {{ $errors->has('fechaInicio') ? 'is-invalid' : '' }}">
                        </div>
                        <div class="form-group">
                            <label for="fechaFin" class="form-label">Fecha de fin</label>
                            <input type="date" id="fechaFin" name="fechaFin"
                                   value="{{ old('fechaFin') }}"
                                   class="form-input {{ $errors->has('fechaFin') ? 'is-invalid' : '' }}">
                        </div>
                    </div>
                </div>

                <div class="form-group" id="motivo-wrap" hidden>
                    <label for="motivo" class="form-label">Motivo <span class="req">*</span></label>
                    <textarea id="motivo" name="motivo" rows="3" maxlength="500"
                              class="form-textarea {{ $errors->has('motivo') ? 'is-invalid' : '' }}"
                              placeholder="Describe el motivo del cambio...">{{ old('motivo') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Confirmar cambio
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-archivar" role="dialog" aria-modal="true" aria-labelledby="modal-archivar-title">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" action="{{ route('arbitros.archivar', $arbitro->idArbitro) }}"
              id="form-archivar"
              data-confirm-nombre="{{ $arbitro->usuario->nombreUsuario }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title" id="modal-archivar-title">
                    <i class="fa-solid fa-box-archive"></i>
                    Archivar árbitro
                </h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="archive-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <p class="archive-warning-title">Esta acción archivará al árbitro</p>
                        <p class="archive-warning-text">
                            <strong>{{ $arbitro->usuario->nombreUsuario }}</strong> dejará de aparecer
                            en el listado activo y no podrá iniciar sesión.
                            Podrás restaurarlo desde árbitros archivados.
                        </p>
                    </div>
                </div>

                <div class="form-group form-group--spaced">
                    <label for="motivo-archivar" class="form-label">Motivo del archivado <span class="req">*</span></label>
                    <input type="text"
                           id="motivo-archivar"
                           name="motivo"
                           maxlength="150"
                           required
                           value="{{ old('motivo') }}"
                           placeholder="Motivo del archivado..."
                           class="form-input {{ $errors->has('motivo') ? 'is-invalid' : '' }}">
                    <div class="counter-wrap">
                        <span id="contador-motivo" class="counter-text">{{ strlen(old('motivo', '')) }}/150</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-box-archive"></i>
                    Archivar árbitro
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
