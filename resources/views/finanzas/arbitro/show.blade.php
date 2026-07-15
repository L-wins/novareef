@extends('layouts.app')

@section('titulo', 'Ficha financiera')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\MovimientoFinanciero;
    $etiquetasEstado = MovimientoFinanciero::ETIQUETAS_ESTADO;
    $nombre = $arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $arbitro->idArbitro;
    $neto   = $estadoCuenta['saldoPendienteCobrar'] - $estadoCuenta['saldoPorCobrar'];
@endphp

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.balance.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $nombre }}</h1>
            <p class="page-subheading">
                <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}">Ver perfil del árbitro</a>
                @if ($arbitro->categoria) · {{ $arbitro->categoria->nombreCategoria }} @endif
            </p>
        </div>
        @can('crear-finanzas')
            <div class="page-header-actions">
                <button type="button" class="btn btn-primary" data-open-modal="cargo">
                    <i class="fa-solid fa-plus"></i>
                    Registrar cargo
                </button>
            </div>
        @endcan
    </div>

    @if ($errors->any())
        <div class="flash-error">Revisa los campos marcados abajo.</div>
    @endif
    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <div class="fin-stats">
        <div class="fin-stat">
            <p class="fin-stat__label">Le debemos</p>
            <p class="fin-stat__value monto-egreso">${{ number_format($estadoCuenta['saldoPendienteCobrar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Nómina y árbitro externo pendiente</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Nos debe</p>
            <p class="fin-stat__value monto-ingreso">${{ number_format($estadoCuenta['saldoPorCobrar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Mensualidad y multa pendiente</p>
        </div>
        <div class="fin-stat fin-stat--hero">
            <p class="fin-stat__label">Neto</p>
            {{-- El color no basta para distinguir negativo — el paréntesis con
                 signo lo deja inequívoco aunque no se perciba el color. --}}
            <p class="fin-stat__value {{ $neto >= 0 ? 'monto-egreso' : 'monto-ingreso' }}">
                {{ $neto >= 0 ? '$' . number_format($neto, 0, ',', '.') : '(-$' . number_format(abs($neto), 0, ',', '.') . ')' }}
            </p>
            <p class="fin-stat__sub">{{ $neto >= 0 ? 'A favor del árbitro' : 'A favor del colegio' }}</p>
        </div>
    </div>

    {{-- ===== Partidos pendientes de pago (le debemos) ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Partidos pendientes de pago</p>
        </div>
        @if ($estadoCuenta['pendientesPorPartido']->isEmpty())
            <div class="card-empty-note">No hay partidos pendientes de pago.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            @can('crear-finanzas') <th></th> @endcan
                            <th>Fecha</th>
                            <th>Torneo</th>
                            <th>Concepto</th>
                            <th class="text-right">Saldo pendiente</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['pendientesPorPartido'] as $mov)
                            <tr>
                                @can('crear-finanzas')
                                    <td>
                                        <input type="checkbox" class="check-nomina-ficha" value="{{ $mov->idMovimiento }}" data-saldo="{{ $mov->saldoPendiente() }}">
                                    </td>
                                @endcan
                                <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                                <td>{{ $mov->torneo->nombreTorneo ?? '—' }}</td>
                                <td class="td-primary">{{ $mov->concepto }}</td>
                                <td class="text-right"><span class="monto-egreso">${{ number_format($mov->saldoPendiente(), 0, ',', '.') }}</span></td>
                                <td class="text-right">@include('finanzas.partials.historial-boton', ['mov' => $mov])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('crear-finanzas')
                {{-- Se muestra en cuanto hay al menos un partido marcado — sirve
                     tanto para pagar uno solo como varios de una vez. --}}
                <div class="ficha-pago-bar" id="ficha-pago-bar" style="display:none;">
                    <span>Seleccionados: <strong id="ficha-pago-total">$0</strong></span>
                    <button type="button" class="btn btn-primary btn-sm" data-open-modal="pagar-nomina">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Pagar seleccionados
                    </button>
                </div>
            @endcan
        @endif
    </div>

    {{-- ===== Cuotas y multas pendientes (nos debe) ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Cuotas y multas pendientes</p>
        </div>
        @if ($estadoCuenta['pendientesPorCuota']->isEmpty())
            <div class="card-empty-note">No hay cuotas ni multas pendientes.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Concepto</th>
                            <th class="text-right">Saldo pendiente</th>
                            @can('crear-finanzas') <th class="text-right">Acciones</th> @endcan
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['pendientesPorCuota'] as $mov)
                            @php
                                $montoACompensar = min($mov->saldoPendiente(), $estadoCuenta['saldoPendienteCobrar']);
                                $quedaPendiente  = $mov->saldoPendiente() - $montoACompensar;
                            @endphp
                            <tr>
                                <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                                <td>{{ $mov->etiquetaCategoria() }}</td>
                                <td class="td-primary">{{ $mov->concepto }}</td>
                                <td class="text-right"><span class="monto-ingreso">${{ number_format($mov->saldoPendiente(), 0, ',', '.') }}</span></td>
                                @can('crear-finanzas')
                                    <td class="text-right">
                                        @if ($estadoCuenta['saldoPendienteCobrar'] > 0)
                                            <form method="POST" action="{{ route('finanzas.arbitro.cargos.compensar', [$arbitro->idArbitro, $mov->idMovimiento]) }}"
                                                  style="display:inline-block;" data-confirm-submit
                                                  data-confirm-title="¿Compensar con nómina?"
                                                  data-confirm-text="Se compensarán ${{ number_format($montoACompensar, 0, ',', '.') }} de «{{ $mov->concepto }}» contra la nómina pendiente del árbitro.{{ $quedaPendiente > 0 ? ' Quedarán $' . number_format($quedaPendiente, 0, ',', '.') . ' pendientes de esta deuda (no alcanza toda la nómina disponible).' : ' Queda saldada por completo.' }}"
                                                  data-confirm-color="#4f8ef7"
                                                  data-confirm-btn="Sí, compensar">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-right-left"></i> Compensar
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-secondary btn-sm" data-open-modal="abono"
                                                data-abono-url="{{ route('finanzas.arbitro.cargos.abonar', [$arbitro->idArbitro, $mov->idMovimiento]) }}"
                                                data-abono-saldo="{{ $mov->saldoPendiente() }}">
                                            <i class="fa-solid fa-hand-holding-dollar"></i> Abonar
                                        </button>
                                        @if ($mov->abonos->isEmpty())
                                            <form method="POST" action="{{ route('finanzas.arbitro.cargos.anular', [$arbitro->idArbitro, $mov->idMovimiento]) }}"
                                                  style="display:inline-block;" data-confirm-submit
                                                  data-confirm-title="¿Anular este cargo?"
                                                  data-confirm-text="Se anulará «{{ $mov->concepto }}» ({{ $mov->etiquetaCategoria() }}) por ${{ number_format((float) $mov->montoTotal, 0, ',', '.') }}. Esta acción no se puede deshacer."
                                                  data-confirm-color="#ef4444"
                                                  data-confirm-btn="Sí, anular">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fa-solid fa-ban"></i> Anular
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endcan
                                <td class="text-right">@include('finanzas.partials.historial-boton', ['mov' => $mov])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Historial de pagos recibidos (nómina) ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Historial de pagos recibidos</p>
        </div>
        @if ($estadoCuenta['historialPagos']->isEmpty())
            <div class="card-empty-note">Aún no se han registrado pagos a este árbitro.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Torneo</th>
                            <th class="text-right">Monto</th>
                            <th>Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['historialPagos'] as $abono)
                            <tr>
                                <td>{{ $abono->fechaAbono->format('d/m/Y') }}</td>
                                <td>{{ $abono->movimiento->torneo->nombreTorneo ?? '—' }}</td>
                                <td class="text-right"><span class="monto-egreso">${{ number_format((float) $abono->monto, 0, ',', '.') }}</span></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $abono->metodoPago)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Historial de pagos hechos (cuota/multa) ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Historial de pagos hechos por el árbitro</p>
        </div>
        @if ($estadoCuenta['historialPagosHechos']->isEmpty())
            <div class="card-empty-note">Este árbitro aún no ha pagado ninguna cuota ni multa.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th class="text-right">Monto</th>
                            <th>Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['historialPagosHechos'] as $abono)
                            <tr>
                                <td>{{ $abono->fechaAbono->format('d/m/Y') }}</td>
                                <td class="td-primary">{{ $abono->movimiento->concepto ?? '—' }}</td>
                                <td class="text-right"><span class="monto-ingreso">${{ number_format((float) $abono->monto, 0, ',', '.') }}</span></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $abono->metodoPago)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Historial de multas ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Historial de multas</p>
        </div>
        @if ($estadoCuenta['historialMultas']->isEmpty())
            <div class="card-empty-note">Este árbitro no tiene multas registradas.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th class="text-right">Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['historialMultas'] as $multa)
                            @php [$label, $color] = $etiquetasEstado[$multa->estadoMovimiento] ?? ['—', 'gray']; @endphp
                            <tr>
                                <td>{{ $multa->fechaMovimiento->format('d/m/Y') }}</td>
                                <td class="td-primary">{{ $multa->concepto }}</td>
                                <td class="text-right"><span class="monto-ingreso">${{ number_format((float) $multa->montoTotal, 0, ',', '.') }}</span></td>
                                <td><span class="badge badge-{{ $color }}">{{ $label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Descuentos aplicados en nómina ===== --}}
    <div class="form-card">
        <div class="form-section form-section--titulo form-section--sin-borde">
            <p class="form-section-title">Descuentos aplicados en nómina</p>
        </div>
        @if ($estadoCuenta['descuentosNomina']->isEmpty())
            <div class="card-empty-note">No se han aplicado descuentos en nómina a este árbitro.</div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto compensado</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estadoCuenta['descuentosNomina'] as $descuento)
                            <tr>
                                <td>{{ $descuento->fechaAbono->format('d/m/Y') }}</td>
                                <td class="td-primary">{{ $descuento->movimiento->concepto ?? '—' }}</td>
                                <td class="text-right"><span class="monto-egreso">${{ number_format((float) $descuento->monto, 0, ',', '.') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Lotes de pago recientes ===== --}}
    @if ($lotesRecientes->isNotEmpty())
        <div class="form-card">
            <div class="form-section form-section--titulo form-section--sin-borde">
                <p class="form-section-title">Comprobantes de pago recientes</p>
            </div>
            <div class="fin-lotes">
                @foreach ($lotesRecientes as $lote)
                    <div class="fin-lote">
                        <span class="fin-lote__info">
                            {{ \Illuminate\Support\Carbon::parse($lote->fecha)->format('d/m/Y') }}
                            <span class="fin-lote__monto">${{ number_format($lote->neto, 0, ',', '.') }}</span>
                        </span>
                        <a href="{{ route('finanzas.arbitro.comprobante', [$arbitro->idArbitro, $lote->idLotePago]) }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @can('crear-finanzas')
        {{-- ===== Modal: registrar cargo ===== --}}
        <div class="nova-modal-overlay" id="modal-cargo" style="display:none;" data-close-on-overlay>
            <div class="nova-modal nova-modal--form">
                <div class="nova-modal__header">
                    <h2><i class="fa-solid fa-plus"></i> Registrar cargo</h2>
                    <button type="button" class="nova-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('finanzas.arbitro.cargos.store', $arbitro->idArbitro) }}">
                    @csrf
                    <div class="nova-modal__body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Categoría <span class="req">*</span></label>
                                <select name="categoria" data-nova-select class="form-select">
                                    <option value="mensualidad">Mensualidad</option>
                                    <option value="multa">Multa</option>
                                    <option value="otro_ingreso">Otro ingreso</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha <span class="req">*</span></label>
                                <input type="text" name="fechaMovimiento" data-nova-date placeholder="dd/mm/aaaa" class="form-input">
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Concepto <span class="req">*</span></label>
                                <input type="text" name="concepto" maxlength="255" placeholder="Ej. Mensualidad julio 2026" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monto <span class="req">*</span></label>
                                <input type="number" name="montoTotal" min="0.01" step="0.01" placeholder="0.00" class="form-input">
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-textarea"></textarea>
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

        {{-- ===== Modal: pagar nómina en lote (uno o varios partidos marcados) ===== --}}
        <div class="nova-modal-overlay" id="modal-pagar-nomina" style="display:none;" data-close-on-overlay>
            <div class="nova-modal nova-modal--form">
                <div class="nova-modal__header">
                    <h2><i class="fa-solid fa-hand-holding-dollar"></i> Pagar nómina</h2>
                    <button type="button" class="nova-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('finanzas.arbitro.nomina.pagar', $arbitro->idArbitro) }}" id="form-pagar-nomina">
                    @csrf
                    <div class="nova-modal__body">
                        <p class="field-hint" id="ficha-pago-resumen"></p>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Fecha <span class="req">*</span></label>
                                <input type="text" name="fecha" data-nova-date placeholder="dd/mm/aaaa" class="form-input">
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
                            <div class="form-group span-2">
                                <label class="form-label">Referencia</label>
                                <input type="text" name="referencia" maxlength="100" class="form-input">
                            </div>
                        </div>
                        <div id="ficha-pago-hidden-inputs"></div>
                    </div>
                    <div class="nova-modal__footer">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== Modal: abonar (compartido, la fila lo apunta al movimiento correcto) ===== --}}
        <div class="nova-modal-overlay" id="modal-abono" style="display:none;" data-close-on-overlay>
            <div class="nova-modal nova-modal--form">
                <div class="nova-modal__header">
                    <h2><i class="fa-solid fa-hand-holding-dollar"></i> Registrar abono</h2>
                    <button type="button" class="nova-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" id="form-abono-arbitro" data-abono-form>
                    @csrf
                    <div class="nova-modal__body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Monto <span class="req">*</span></label>
                                <input type="number" name="monto" min="0.01" step="0.01" class="form-input" data-abono-monto>
                                <p class="field-hint" data-abono-hint></p>
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
    @endcan

    {{-- ===== Modal: historial (compartido, cada botón carga su propio <template>) ===== --}}
    <div class="nova-modal-overlay" id="modal-historial" style="display:none;" data-close-on-overlay>
        <div class="nova-modal">
            <div class="nova-modal__header">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Historial</h2>
                <button type="button" class="nova-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="nova-modal__body" id="historial-modal-body"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js', 'resources/js/finanzas/ficha-arbitro.js'])
@endpush
