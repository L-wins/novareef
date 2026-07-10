@extends('layouts.app')

@section('titulo', 'Detalle del movimiento')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\MovimientoFinanciero;
    $etiquetasCategoria = MovimientoFinanciero::ETIQUETAS_CATEGORIA;
    $etiquetasEstado    = MovimientoFinanciero::ETIQUETAS_ESTADO;
    [$estadoLabel, $estadoColor] = $etiquetasEstado[$movimiento->estadoMovimiento] ?? ['—', 'gray'];
    $saldoPendiente = $movimiento->saldoPendiente();
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $movimiento->concepto }}</h1>
            <p class="page-subheading">
                <span class="badge {{ $movimiento->esIngreso() ? 'badge-green' : 'badge-red' }}">
                    {{ $movimiento->esIngreso() ? 'Ingreso' : 'Egreso' }}
                </span>
                {{ $etiquetasCategoria[$movimiento->categoria] ?? $movimiento->categoria }}
            </p>
        </div>
        <div class="page-header-actions">
            @can('crear-finanzas')
                @if ($movimiento->estadoMovimiento !== 'anulado' && $saldoPendiente > 0)
                    <button type="button" class="btn btn-primary" data-open-modal="abono">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        Registrar abono
                    </button>
                @endif
                @if ($movimiento->estadoMovimiento === 'pendiente' && $movimiento->abonos->isEmpty())
                    <form method="POST" action="{{ route('finanzas.anular', $movimiento->idMovimiento) }}"
                          data-confirm-submit
                          data-confirm-title="¿Anular movimiento?"
                          data-confirm-text="Esta acción no se puede deshacer."
                          data-confirm-color="#ef4444"
                          data-confirm-btn="Sí, anular">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-ban"></i>
                            Anular
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <div class="form-card">
        <div class="form-section">
            <p class="form-section-title">Resumen</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label">Monto total</label>
                    <span class="{{ $movimiento->esIngreso() ? 'monto-ingreso' : 'monto-egreso' }} monto-md">
                        ${{ number_format((float) $movimiento->montoTotal, 0, ',', '.') }}
                    </span>
                </div>
                <div class="form-group">
                    <label class="form-label">Saldo pendiente</label>
                    <span class="monto-md-bold">${{ number_format($saldoPendiente, 0, ',', '.') }}</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <span class="badge badge-{{ $estadoColor }}">{{ $estadoLabel }}</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha</label>
                    <span>{{ $movimiento->fechaMovimiento->format('d/m/Y') }}</span>
                </div>
                @if ($movimiento->arbitro)
                    <div class="form-group">
                        <label class="form-label">Árbitro</label>
                        <span>{{ $movimiento->arbitro->usuario->nombreUsuario ?? '—' }}</span>
                    </div>
                @elseif ($movimiento->nombreArbitroExterno)
                    <div class="form-group">
                        <label class="form-label">Árbitro externo</label>
                        <span>{{ $movimiento->nombreArbitroExterno }} ({{ $movimiento->documentoArbitroExterno }})</span>
                    </div>
                @endif
                @if ($movimiento->torneo)
                    <div class="form-group">
                        <label class="form-label">Torneo</label>
                        <span>{{ $movimiento->torneo->nombreTorneo }}</span>
                    </div>
                @endif
                @if ($movimiento->observaciones)
                    <div class="form-group span-2">
                        <label class="form-label">Observaciones</label>
                        <span>{{ $movimiento->observaciones }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section form-section--titulo">
            <p class="form-section-title">Abonos registrados</p>
        </div>
        @if ($movimiento->abonos->isEmpty())
            <div class="card-empty-note">
                Aún no se han registrado abonos.
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movimiento->abonos as $abono)
                        <tr>
                            <td>{{ $abono->fechaAbono->format('d/m/Y') }}</td>
                            <td>${{ number_format((float) $abono->monto, 0, ',', '.') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $abono->metodoPago)) }}</td>
                            <td>{{ $abono->referencia ?? '—' }}</td>
                            <td>{{ $abono->usuarioRegistro->nombreUsuario ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Historial</p>
        </div>
        <div class="historial-lista">
            @foreach ($movimiento->historial as $item)
                <div class="historial-item">
                    <span class="td-primary">{{ ucfirst($item->tipoAccion) }}</span>
                    <span class="td-secondary">
                        {{ $item->created_at->format('d/m/Y H:i') }}
                        @if ($item->usuarioAccion) — {{ $item->usuarioAccion->nombreUsuario }} @endif
                        @if ($item->detalle) — {{ $item->detalle }} @endif
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal de abono --}}
    <div class="nova-modal-overlay" id="modal-abono" style="display:none;" data-close-on-overlay>
        <div class="nova-modal nova-modal--form">
            <div class="nova-modal__header">
                <h2><i class="fa-solid fa-hand-holding-dollar"></i> Registrar abono</h2>
                <button type="button" class="nova-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('finanzas.abonar', $movimiento->idMovimiento) }}">
                @csrf
                <div class="nova-modal__body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Monto <span class="req">*</span></label>
                            <input type="number" name="monto" min="0.01" max="{{ $saldoPendiente }}" step="0.01"
                                   value="{{ old('monto') }}" class="form-input">
                            <p class="field-hint">Saldo pendiente: ${{ number_format($saldoPendiente, 0, ',', '.') }}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha <span class="req">*</span></label>
                            <input type="text" name="fechaAbono" data-nova-date placeholder="dd/mm/aaaa" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Método de pago <span class="req">*</span></label>
                            <select name="metodoPago" data-nova-select class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="consignacion">Consignación</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Referencia</label>
                            <input type="text" name="referencia" maxlength="100" class="form-input">
                        </div>
                    </div>
                </div>
                <div class="nova-modal__footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
