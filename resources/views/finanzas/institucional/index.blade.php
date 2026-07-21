@extends('layouts.app')

@section('titulo', 'Gastos e ingresos')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@php
    use App\Models\AbonoMovimiento;
    use App\Models\MovimientoFinanciero;
    $etiquetasCategoria = MovimientoFinanciero::ETIQUETAS_CATEGORIA;
    $etiquetasMetodo = [
        AbonoMovimiento::METODO_EFECTIVO     => 'Efectivo',
        AbonoMovimiento::METODO_PAGO_DIGITAL => 'Pago digital',
    ];
    $categoriasIngreso   = array_intersect_key($etiquetasCategoria, array_flip([
        MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO,
        MovimientoFinanciero::CATEGORIA_OTRO_INGRESO,
    ]));
    $categoriasEgreso    = array_intersect_key($etiquetasCategoria, array_flip([
        MovimientoFinanciero::CATEGORIA_GASTO_FIJO,
        MovimientoFinanciero::CATEGORIA_GASTO_INSTITUCIONAL,
        MovimientoFinanciero::CATEGORIA_GASTO_VARIO,
    ]));
@endphp

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Gastos e ingresos</h1>
            <p class="page-subheading">Movimientos institucionales del colegio — sin árbitro asociado. Se registran ya pagados.</p>
        </div>
        @can('crear-finanzas')
            <div class="page-header-actions">
                <button type="button" class="btn btn-primary" data-open-modal="registrar">
                    <i class="fa-solid fa-plus"></i>
                    Registrar
                </button>
            </div>
        @endcan
    </div>

    @include('finanzas.partials.subnav')

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
            <p class="fin-stat__label">Ingresos</p>
            <p class="fin-stat__value monto-ingreso">${{ number_format($resumen['totalIngresos'], 0, ',', '.') }}</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Egresos</p>
            <p class="fin-stat__value monto-egreso">${{ number_format($resumen['totalEgresos'], 0, ',', '.') }}</p>
        </div>
        <div class="fin-stat fin-stat--hero">
            <p class="fin-stat__label">Neto</p>
            <p class="fin-stat__value {{ $resumen['neto'] >= 0 ? 'monto-ingreso' : 'monto-egreso' }}">
                {{ $resumen['neto'] >= 0 ? '$' . number_format($resumen['neto'], 0, ',', '.') : '(-$' . number_format(abs($resumen['neto']), 0, ',', '.') . ')' }}
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('finanzas.institucional.index') }}" class="filter-bar-grid" data-auto-filter>
        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipoMovimiento" data-nova-select class="filter-select">
                <option value="">Todos</option>
                <option value="ingreso" @selected(($filtros['tipoMovimiento'] ?? '') === 'ingreso')>Ingreso</option>
                <option value="egreso" @selected(($filtros['tipoMovimiento'] ?? '') === 'egreso')>Egreso</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Categoría</label>
            <select name="categoria" data-nova-select class="filter-select">
                <option value="">Todas</option>
                @foreach ($etiquetasCategoria as $valor => $etiqueta)
                    @if (in_array($valor, MovimientoFinanciero::CATEGORIAS_INSTITUCIONALES, true))
                        <option value="{{ $valor }}" @selected(($filtros['categoria'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Desde</label>
            <input type="text" name="desde" data-nova-date placeholder="dd/mm/aaaa" class="filter-input" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div class="filter-group">
            <label class="filter-label">Hasta</label>
            <input type="text" name="hasta" data-nova-date placeholder="dd/mm/aaaa" class="filter-input" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        <div class="filter-group">
            <label class="filter-label">Buscar</label>
            <input type="search" name="q" placeholder="Concepto…" class="filter-input" value="{{ $filtros['q'] ?? '' }}" autocomplete="off">
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm" data-auto-filter-hide>Filtrar</button>
        </div>
    </form>

    @if ($movimientos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <p>No hay movimientos institucionales con estos filtros.</p>
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Categoría</th>
                        <th>Torneo</th>
                        <th class="text-right">Monto</th>
                        <th>Método de pago</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimientos as $mov)
                        <tr>
                            <td>{{ $mov->fechaMovimiento->format('d/m/Y') }}</td>
                            <td class="td-primary">{{ $mov->concepto }}</td>
                            <td>{{ $mov->etiquetaCategoria() }}</td>
                            <td>{{ $mov->torneo->nombreTorneo ?? '—' }}</td>
                            <td class="text-right">
                                <span class="{{ $mov->esIngreso() ? 'monto-ingreso' : 'monto-egreso' }}">
                                    ${{ number_format((float) $mov->montoTotal, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>{{ $etiquetasMetodo[$mov->abonos->first()?->metodoPago] ?? '—' }}</td>
                            <td class="text-right">@include('finanzas.partials.historial-boton', ['mov' => $mov])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">{{ $movimientos->links() }}</div>
    @endif

    @can('crear-finanzas')
        {{-- ===== Modal: registrar movimiento institucional (ya pagado) ===== --}}
        {{-- Se auto-abre si viene de un enlace con ?abrir=registrar (ej. "Registrar
             ingreso de torneo" desde la ficha del torneo) o si la última
             validación falló. --}}
        <div class="nova-modal-overlay" id="modal-registrar"
             style="display: {{ $errors->any() || request('abrir') === 'registrar' ? 'flex' : 'none' }};" data-close-on-overlay>
            <div class="nova-modal nova-modal--form">
                <div class="nova-modal__header">
                    <h2><i class="fa-solid fa-plus"></i> Registrar movimiento</h2>
                    <button type="button" class="nova-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('finanzas.institucional.store') }}">
                    @csrf
                    <div class="nova-modal__body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Tipo <span class="req">*</span></label>
                                @php
                                    $categoriaPrefill = old('categoria', request('categoria'));
                                    $tipoPrefill      = old('tipoMovimiento', in_array($categoriaPrefill, array_keys($categoriasEgreso), true) ? 'egreso' : 'ingreso');
                                    $torneoPrefill    = old('idTorneo', request('idTorneo'));
                                @endphp
                                <select name="tipoMovimiento" id="inst-tipo" class="form-select" data-nova-select>
                                    <option value="ingreso" @selected($tipoPrefill === 'ingreso')>Ingreso</option>
                                    <option value="egreso" @selected($tipoPrefill === 'egreso')>Egreso</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categoría <span class="req">*</span></label>
                                <select name="categoria" id="inst-categoria" class="form-select" data-nova-select>
                                    @foreach ($categoriasIngreso as $valor => $etiqueta)
                                        <option value="{{ $valor }}" data-tipo="ingreso" @selected($categoriaPrefill === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                    @foreach ($categoriasEgreso as $valor => $etiqueta)
                                        <option value="{{ $valor }}" data-tipo="egreso" @selected($categoriaPrefill === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group span-2" id="inst-torneo-wrap">
                                <label class="form-label">Torneo</label>
                                <select name="idTorneo" data-nova-select data-searchable="true" class="form-select">
                                    <option value="">— Ninguno —</option>
                                    @foreach ($torneos as $torneo)
                                        <option value="{{ $torneo->idTorneo }}" @selected((string) $torneoPrefill === (string) $torneo->idTorneo)>{{ $torneo->nombreTorneo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha <span class="req">*</span></label>
                                <input type="text" name="fechaMovimiento" data-nova-date placeholder="dd/mm/aaaa" class="form-input" value="{{ old('fechaMovimiento') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monto <span class="req">*</span></label>
                                <input type="number" name="montoTotal" min="0.01" step="0.01" placeholder="0.00" class="form-input" value="{{ old('montoTotal') }}">
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Concepto <span class="req">*</span></label>
                                <input type="text" name="concepto" maxlength="255" placeholder="Ej. Arriendo de sede julio 2026" class="form-input" value="{{ old('concepto') }}">
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Método de pago <span class="req">*</span></label>
                                <select name="metodoPago" data-nova-select class="form-select">
                                    <option value="efectivo" @selected(old('metodoPago', 'efectivo') === 'efectivo')>Efectivo</option>
                                    <option value="pago_digital" @selected(old('metodoPago') === 'pago_digital')>Pago digital</option>
                                </select>
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-textarea">{{ old('observaciones') }}</textarea>
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
<script>
    window.institucionalCategorias = {
        ingreso: @json(collect($categoriasIngreso)->map(fn ($etiqueta, $valor) => ['value' => $valor, 'label' => $etiqueta])->values()),
        egreso:  @json(collect($categoriasEgreso)->map(fn ($etiqueta, $valor) => ['value' => $valor, 'label' => $etiqueta])->values()),
    };
</script>
    @vite(['resources/js/finanzas/finanzas.js', 'resources/js/finanzas/institucional.js'])
@endpush
