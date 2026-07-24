@extends('layouts.app')

@section('titulo', 'Detalle de la sanción')
@section('seccion', 'Sanciones')

@push('styles')
    @vite(['resources/css/sanciones/sanciones.css'])
@endpush

@php
    [$estadoLabel, $estadoColor] = \App\Models\Sancion::ETIQUETAS_ESTADO[$sancion->estadoSancion] ?? ['—', 'gray'];
    $severidad = $sancion->tipo?->severidad;
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('sanciones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a {{ $esArbitro ? 'mis sanciones' : 'sanciones' }}
    </a>

    <div class="sancion-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $sancion->tipo->etiqueta ?? 'Sanción' }}</h1>
            <p class="page-subheading">
                <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                @if ($severidad)
                    <span class="sev-chip" data-severidad="{{ $severidad }}">
                        <i class="fa-solid fa-circle-exclamation"></i>{{ ucfirst($severidad) }}
                    </span>
                @endif
                @unless ($esArbitro)
                    {{ $sancion->arbitro->usuario->nombreUsuario ?? '—' }}
                @endunless
            </p>
        </div>

        <div class="sancion-actions-bar">
            <a href="{{ route('sanciones.acta', $sancion->idSancion) }}" class="btn btn-secondary">
                <i class="fa-solid fa-file-pdf"></i>
                Descargar acta
            </a>

            @if ($esArbitro)
                {{-- Apelar es exclusivo del árbitro dueño de la sanción — ver
                     SancionController::autorizarApelacion(). El comité no
                     tiene este botón: si quiere dejar sin efecto una sanción
                     por su cuenta, usa Anular. --}}
                @if ($sancion->puedeApelarse())
                    <button type="button" class="btn btn-primary" data-accion-sancion="apelar">
                        <i class="fa-solid fa-gavel"></i> Apelar sanción
                    </button>
                @endif
            @else
                @can('crear-sanciones')
                    @if (in_array($sancion->estadoSancion, ['activa', 'apelada']))
                        <button type="button" class="btn btn-primary" data-accion-sancion="cumplir">
                            <i class="fa-solid fa-check"></i> Cumplir
                        </button>
                    @endif
                @endcan
                @can('editar-sanciones')
                    @if ($sancion->estadoSancion === 'apelada')
                        <button type="button" class="btn btn-secondary" data-accion-sancion="resolver">
                            <i class="fa-solid fa-scale-balanced"></i> Resolver apelación
                        </button>
                    @endif
                    @if ($sancion->estadoSancion !== 'anulada')
                        <button type="button" class="btn btn-danger" data-accion-sancion="anular">
                            <i class="fa-solid fa-ban"></i> Anular
                        </button>
                    @endif
                @endcan
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    @if ($esReincidente)
        <div class="reincidente-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong>Árbitro reincidente:</strong> acumula {{ $totalReciente }} sanciones en los últimos 6 meses (incluida esta).</span>
        </div>
    @endif

    @if ($sancion->estaActiva())
        @include('sanciones.partials.plazo-apelacion')
    @endif

    <div class="form-card sancion-card--severidad" data-severidad="{{ $severidad }}">
        <div class="form-section">
            <p class="form-section-title">Detalle del hecho</p>
            <div class="detail-grid">
                <div class="detail-field">
                    <span class="detail-field__label">Fecha del hecho</span>
                    <span class="detail-field__value">{{ $sancion->fechaHecho->format('d/m/Y') }}</span>
                </div>
                <div class="detail-field">
                    <span class="detail-field__label">Suspensión</span>
                    <span class="detail-field__value">
                        @if ($sancion->tieneSuspension())
                            {{ $sancion->fechaInicioSancion->format('d/m/Y') }}
                            —
                            {{ $sancion->fechaFinSancion?->format('d/m/Y') ?? 'indefinida' }}
                        @else
                            <span class="detail-field__value--muted">Sin suspensión — solo registro{{ $sancion->tieneMultaEconomica ? ' y multa económica' : '' }}</span>
                        @endif
                    </span>
                </div>
                @if ($sancion->tipo?->articuloReglamento)
                    <div class="detail-field span-2">
                        <span class="detail-field__label">Fundamento reglamentario</span>
                        <span class="detail-field__value">{{ $sancion->tipo->articuloReglamento }}</span>
                    </div>
                @endif
                <div class="detail-field span-2">
                    <span class="detail-field__label">Motivo</span>
                    <span class="detail-field__value">{{ $sancion->motivoSancion }}</span>
                </div>
                @if ($sancion->tieneMultaEconomica && $sancion->movimientoFinanciero)
                    <div class="detail-field span-2">
                        <span class="detail-field__label">Multa económica asociada</span>
                        <div class="multa-card">
                            <span class="multa-card__amount">
                                ${{ number_format((float) $sancion->movimientoFinanciero->montoTotal, 2) }}
                                <span>Estado: {{ $sancion->movimientoFinanciero->estadoMovimiento }}</span>
                            </span>
                        </div>
                    </div>
                @endif
                <div class="detail-field">
                    <span class="detail-field__label">Registrada por</span>
                    <span class="detail-field__value">{{ $sancion->usuarioImpuso->nombreUsuario ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Historial</p>
        </div>
        @include('sanciones.partials.timeline')
    </div>

    {{-- Modal de cambio de estado --}}
    <div class="nova-modal-overlay" id="modal-cambiar-estado" style="display:none;" data-close-on-overlay>
        <div class="nova-modal">
            <div class="nova-modal__header">
                <h2 id="modal-estado-titulo"><i class="fa-solid fa-shield-halved"></i> Cambiar estado</h2>
                <button type="button" class="nova-modal__close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('sanciones.estado', $sancion->idSancion) }}" id="form-cambiar-estado">
                @csrf
                @method('PUT')
                <input type="hidden" name="accion" id="accion-input" value="">
                <div class="nova-modal__body">
                    <div class="form-grid">
                        <div class="form-group" id="wrap-resultado" style="display:none;">
                            <label class="form-label">Resultado de la apelación</label>
                            <select name="resultado" class="form-select">
                                <option value="confirmada">Confirmada — la sanción se sostiene</option>
                                <option value="revocada">Revocada — la sanción queda sin efecto</option>
                            </select>
                        </div>
                        <div class="form-group" id="wrap-motivo">
                            <label class="form-label" id="label-motivo">Motivo</label>
                            <textarea name="motivo" id="textarea-motivo" class="form-textarea"></textarea>
                        </div>
                    </div>
                </div>
                <div class="nova-modal__footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
