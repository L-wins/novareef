@extends('layouts.app')

@section('titulo', 'Detalle de la sanción')
@section('seccion', 'Sanciones')

@push('styles')
    @vite(['resources/css/sanciones/sanciones.css'])
@endpush

@php
    $etiquetasEstado = [
        'activa'   => ['Activa', 'amber'],
        'cumplida' => ['Cumplida', 'green'],
        'anulada'  => ['Anulada', 'red'],
        'apelada'  => ['Apelada', 'blue'],
    ];
    [$estadoLabel, $estadoColor] = $etiquetasEstado[$sancion->estadoSancion] ?? ['—', 'gray'];
    $esArbitro = auth()->user()->rolUsuario === 'arbitro';
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('sanciones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a {{ $esArbitro ? 'mis sanciones' : 'sanciones' }}
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $sancion->tipo->etiqueta ?? 'Sanción' }}</h1>
            <p class="page-subheading">
                <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                {{ $sancion->arbitro->usuario->nombreUsuario ?? '—' }}
            </p>
        </div>
        @can('crear-sanciones')
            <div style="display:flex; gap:0.75rem;">
                @if (in_array($sancion->estadoSancion, ['activa', 'apelada']))
                    <button type="button" class="btn btn-primary" data-accion-sancion="cumplir">
                        <i class="fa-solid fa-check"></i> Cumplir
                    </button>
                @endif
                @if ($sancion->estadoSancion === 'activa')
                    <button type="button" class="btn btn-secondary" data-accion-sancion="apelar">
                        <i class="fa-solid fa-gavel"></i> Apelar
                    </button>
                @endif
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
            </div>
        @endcan
    </div>

    @if (session('success'))
        <div class="flash-success" style="margin-bottom:1.25rem;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error" style="margin-bottom:1.25rem;">{{ session('error') }}</div>
    @endif

    <div class="form-card">
        <div class="form-section">
            <p class="form-section-title">Detalle</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label">Fecha del hecho</label>
                    <span>{{ $sancion->fechaHecho->format('d/m/Y') }}</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Vigencia</label>
                    <span>
                        {{ $sancion->fechaInicioSancion->format('d/m/Y') }}
                        —
                        {{ $sancion->fechaFinSancion?->format('d/m/Y') ?? 'indefinida' }}
                    </span>
                </div>
                <div class="form-group span-2">
                    <label class="form-label">Motivo</label>
                    <span>{{ $sancion->motivoSancion }}</span>
                </div>
                @if ($sancion->tieneMultaEconomica && $sancion->movimientoFinanciero)
                    <div class="form-group span-2">
                        <label class="form-label">Multa económica asociada</label>
                        <span>
                            ${{ number_format((float) $sancion->movimientoFinanciero->montoTotal, 2) }}
                            — estado: {{ $sancion->movimientoFinanciero->estadoMovimiento }}
                            @can('ver-finanzas')
                                (<a href="{{ route('finanzas.show', $sancion->movimientoFinanciero->idMovimiento) }}">ver movimiento</a>)
                            @endcan
                        </span>
                    </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Registrada por</label>
                    <span>{{ $sancion->usuarioImpuso->nombreUsuario ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Historial</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach ($sancion->historial as $item)
                <div style="font-size:0.85rem;color:var(--san-text-2);">
                    <span class="td-primary">{{ ucfirst(str_replace('_', ' ', $item->tipoAccion)) }}</span>
                    <span class="td-secondary">
                        {{ $item->created_at->format('d/m/Y H:i') }}
                        @if ($item->usuarioAccion) — {{ $item->usuarioAccion->nombreUsuario }} @endif
                        @if ($item->detalle) — {{ $item->detalle }} @endif
                    </span>
                </div>
            @endforeach
        </div>
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
                            <label class="form-label">Motivo</label>
                            <textarea name="motivo" class="form-textarea"></textarea>
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
