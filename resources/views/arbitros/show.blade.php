@extends('layouts.app')

@section('titulo', $arbitro->usuario->nombreUsuario)
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
@php
    $esPropio   = (int) $arbitro->idUsuario === (int) auth()->id();
    $puedeFoto  = $esPropio || auth()->user()->can('editar-arbitros');
    $porcentaje = $arbitro->porcentajePerfil;
    $colorBar   = $arbitro->colorPerfil;
    $estadoObj  = $arbitro->estado;
@endphp

<div class="container">

    @if ($errors->any())
        <div class="flash-error">
            <strong>Corrige los siguientes errores:</strong>
            <ul style="margin:.4rem 0 0 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="{{ route('arbitros.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a árbitros
    </a>

    {{-- ===== HERO ===== --}}
    <div class="profile-hero">
        <div class="profile-hero-left">

            {{-- Foto de perfil --}}
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
                               style="display:none;">
                    </form>

                    @if ($esPropio && $arbitro->fotoPerfil)
                        <form method="POST" action="{{ route('arbitros.foto.eliminar', $arbitro->idArbitro) }}"
                              class="profile-photo-delete"
                              onsubmit="return confirm('¿Eliminar tu foto de perfil?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-delete" title="Eliminar foto">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    @endif
                @endif
            </div>

            <div class="profile-hero-info">
                <h1 class="profile-hero-name">{{ $arbitro->usuario->nombreUsuario }}</h1>
                <div class="profile-hero-meta">
                    <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                    <span class="estado-pill" data-color="{{ $estadoObj->color ?? 'gray' }}">
                        {{ $estadoObj->etiqueta ?? ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                    </span>
                    <span class="td-code" style="font-size:0.78rem;">{{ $arbitro->codigoCarnet }}</span>
                </div>

                {{-- Barra de progreso --}}
                <div class="profile-progress">
                    <div class="profile-progress-head">
                        <span class="profile-progress-label">Perfil completo</span>
                        <span class="profile-progress-value" data-color="{{ $colorBar }}">{{ $porcentaje }}%</span>
                    </div>
                    <div class="profile-progress-bar">
                        <div class="profile-progress-fill" data-color="{{ $colorBar }}"
                             style="width: {{ $porcentaje }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-hero-actions">
            @can('editar-arbitros')
                <a href="{{ route('arbitros.edit', $arbitro->idArbitro) }}" class="btn btn-secondary">
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
    </div>

    <div class="detail-body">

        {{-- Datos del usuario --}}
        <div class="detail-card">
            <p class="detail-section-title">Datos del usuario</p>
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
        </div>

        {{-- Estado financiero --}}
        @if ($resumenFinanciero !== null)
            <div class="detail-card">
                <p class="detail-section-title">Estado financiero</p>
                <div class="detail-grid">
                    <div class="detail-field">
                        <span class="detail-label">Le debemos</span>
                        <span class="detail-value monto-negativo">${{ number_format($resumenFinanciero['leDebemos'], 0, ',', '.') }}</span>
                    </div>
                    <div class="detail-field">
                        <span class="detail-label">Nos debe</span>
                        <span class="detail-value monto-positivo">${{ number_format($resumenFinanciero['nosDebe'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div style="margin-top:1rem;">
                    <a href="{{ route('finanzas.arbitro.show', $arbitro->idArbitro) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        Ver ficha financiera completa
                    </a>
                </div>
            </div>
        @endif

        {{-- Identificación --}}
        <div class="detail-card">
            <p class="detail-section-title">Identificación</p>
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
                    <span class="detail-value td-code">{{ $arbitro->codigoCarnet }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Categoría</span>
                    <span class="detail-value">{{ $arbitro->categoria->nombreCategoria }}</span>
                </div>
            </div>
        </div>

        {{-- Información física y de salud --}}
        <div class="detail-card">
            <p class="detail-section-title">Información física y de salud</p>
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
            </div>
        </div>

        {{-- Información profesional --}}
        <div class="detail-card">
            <p class="detail-section-title">Información profesional y administrativa</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-label">Profesión</span>
                    <span class="detail-value">{{ $arbitro->profesionArbitro ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Fecha de ingreso al colegio</span>
                    <span class="detail-value">
                        {{ $arbitro->fechaIngresoColegio ? $arbitro->fechaIngresoColegio->format('d/m/Y') : '—' }}
                    </span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Dirección</span>
                    <span class="detail-value">{{ $arbitro->direccionArbitro ?? '—' }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-label">Barrio</span>
                    <span class="detail-value">{{ $arbitro->barrioArbitro ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Vehículo --}}
        <div class="detail-card">
            <p class="detail-section-title">Vehículo</p>
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
        </div>

        {{-- Documentos --}}
        <div class="detail-card">
            <p class="detail-section-title">Documentos</p>
            @forelse ($arbitro->documentos as $documento)
                <div class="docs-list">
                    <div class="doc-item">
                        <i class="fa-solid fa-file-lines"></i>
                        <span>{{ $documento->nombreDocumento }}</span>
                        @if ($documento->obligatorio)
                            <span class="cat-badge" style="margin-left:auto;">Obligatorio</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="detail-empty">No hay documentos cargados para este árbitro.</p>
            @endforelse
        </div>

        {{-- Historial de estados --}}
        @can('editar-arbitros')
        <div class="detail-card">
            <p class="detail-section-title">Historial de estados</p>
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
                                    De
                                    <strong>{{ ucfirst(str_replace('_',' ',$h->estadoAnterior)) }}</strong>
                                    →
                                    <strong>{{ ucfirst(str_replace('_',' ',$h->estadoNuevo)) }}</strong>
                                    · por <strong>{{ $h->usuarioCambio?->nombreUsuario ?? 'Sistema' }}</strong>
                                </p>
                                @if ($h->fechaInicio || $h->fechaFin)
                                    <p class="timeline-meta">
                                        @if ($h->fechaInicio)
                                            <span class="detail-label">Inicio:</span>
                                            {{ $h->fechaInicio->format('d/m/Y') }}
                                        @endif
                                        @if ($h->fechaFin)
                                            · <span class="detail-label">Fin:</span>
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
        </div>
        @endcan

    </div>
</div>

{{-- ===== MODAL CAMBIO DE ESTADO ===== --}}
@can('editar-arbitros')
<div class="modal" id="modal-cambio-estado" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" action="{{ route('arbitros.estado', $arbitro->idArbitro) }}"
              id="form-cambio-estado"
              data-confirm-nombre="{{ $arbitro->usuario->nombreUsuario }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Cambiar estado del árbitro</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="estadoNuevo" class="form-label">Nuevo estado <span class="req">*</span></label>
                    <select id="estadoNuevo" name="estadoNuevo"
                            class="form-select {{ $errors->has('estadoNuevo') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($estados as $est)
                            @if ($est->nombre !== $arbitro->estadoArbitro)
                                <option value="{{ $est->nombre }}" {{ old('estadoNuevo') === $est->nombre ? 'selected' : '' }}>
                                    {{ $est->etiqueta }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="fechas-wrap" style="display:none;">
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

                <div class="form-group" id="motivo-wrap" style="display:none;">
                    <label for="motivo" class="form-label">Motivo <span class="req">*</span></label>
                    <textarea id="motivo" name="motivo" rows="3" maxlength="500"
                              class="form-textarea {{ $errors->has('motivo') ? 'is-invalid' : '' }}"
                              placeholder="Describe el motivo del cambio...">{{ old('motivo') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar cambio</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL ARCHIVAR ÁRBITRO ===== --}}
<div class="modal" id="modal-archivar" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" action="{{ route('arbitros.archivar', $arbitro->idArbitro) }}"
              id="form-archivar"
              data-confirm-nombre="{{ $arbitro->usuario->nombreUsuario }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa-solid fa-box-archive" style="color:#ef4444;margin-right:0.5rem;"></i>
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
                            Podrás restaurarlo desde la sección de árbitros archivados.
                        </p>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1.25rem;">
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
