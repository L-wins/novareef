@extends('layouts.app')

@section('titulo', 'Balance financiero')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Balance financiero</h1>
            <p class="page-subheading">Bolsillos del colegio y estado de cuenta de cada árbitro — entra a su ficha desde cualquier fila.</p>
        </div>
        @can('crear-finanzas')
            <div class="page-header-actions">
                <button type="button" class="btn btn-secondary" data-open-modal="saldo-inicial">
                    <i class="fa-solid fa-vault"></i>
                    {{ $tieneSaldoInicial ? 'Registrar ajuste de saldo' : 'Registrar saldo inicial' }}
                </button>
            </div>
        @endcan
    </div>

    @include('finanzas.partials.subnav')

    @if (session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    {{-- Bolsillos: separan la caja bruta de lo realmente disponible para gastar --}}
    <div class="fin-stats">
        <div class="fin-stat">
            <p class="fin-stat__label">Caja en banco</p>
            <p class="fin-stat__value {{ $bolsillos['saldoEnCaja'] < 0 ? 'monto-egreso' : 'monto-ingreso' }}">
                ${{ number_format($bolsillos['saldoEnCaja'], 0, ',', '.') }}
            </p>
            <p class="fin-stat__sub">Efectivo realmente cobrado menos realmente pagado</p>
        </div>
        <div class="fin-stat fin-stat--hero">
            <p class="fin-stat__label">Disponible real</p>
            <p class="fin-stat__value {{ $bolsillos['disponibleReal'] < 0 ? 'monto-egreso' : 'monto-ingreso' }}">
                ${{ number_format($bolsillos['disponibleReal'], 0, ',', '.') }}
            </p>
            <p class="fin-stat__sub">Caja en banco menos todo lo que aún falta pagar</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Por cobrar</p>
            <p class="fin-stat__value">${{ number_format($bolsillos['pendientePorCobrar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Mensualidades, multas e ingresos de torneo pendientes</p>
        </div>
        <div class="fin-stat">
            <p class="fin-stat__label">Por pagar</p>
            <p class="fin-stat__value">${{ number_format($bolsillos['pendientePorPagar'], 0, ',', '.') }}</p>
            <p class="fin-stat__sub">Nómina de árbitros y gastos pendientes</p>
        </div>
    </div>

    <div class="cuenta-resumen-pago mb-fin">
        <div>
            <p class="form-label">Total que le debemos a árbitros</p>
            <p class="monto-egreso monto-lg">${{ number_format($balance['totalLeDebemos'], 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="form-label">Total que nos deben los árbitros</p>
            <p class="monto-ingreso monto-lg">${{ number_format($balance['totalNosDeben'], 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($balance['porArbitro']->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-user-slash"></i>
            <p>Este colegio todavía no tiene árbitros registrados.</p>
        </div>
    @else
        <div class="cm-toolbar">
            <input type="search" data-balance-filtro placeholder="Buscar árbitro…" class="form-input cm-toolbar__buscar" autocomplete="off">
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Árbitro</th>
                        <th class="text-right">Le debemos</th>
                        <th class="text-right">Nos debe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balance['porArbitro'] as $fila)
                        <tr data-balance-fila data-nombre="{{ mb_strtolower($fila['arbitro']->usuario->nombreUsuario ?? '') }}">
                            <td>
                                <a href="{{ route('finanzas.arbitro.show', $fila['arbitro']->idArbitro) }}" class="td-primary">
                                    {{ $fila['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $fila['arbitro']->idArbitro }}
                                </a>
                            </td>
                            <td class="text-right">
                                @if ($fila['leDebemos'] > 0)
                                    <span class="monto-egreso">${{ number_format($fila['leDebemos'], 0, ',', '.') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($fila['nosDebe'] > 0)
                                    <span class="monto-ingreso">${{ number_format($fila['nosDebe'], 0, ',', '.') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @can('crear-finanzas')
        <div class="nova-modal-overlay" id="modal-saldo-inicial" style="display:none;" data-close-on-overlay>
            <div class="nova-modal nova-modal--form">
                <div class="nova-modal__header">
                    <h2><i class="fa-solid fa-vault"></i> {{ $tieneSaldoInicial ? 'Registrar ajuste de saldo' : 'Registrar saldo inicial' }}</h2>
                    <button type="button" class="nova-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('finanzas.saldo-inicial.store') }}">
                    @csrf
                    <div class="nova-modal__body">
                        @if (! $tieneSaldoInicial)
                            <p class="field-hint">
                                Registra el dinero que el colegio ya tenía en cuenta antes de empezar a usar NovaReef.
                                Queda como efectivo disponible de inmediato, no como un cobro pendiente.
                            </p>
                        @else
                            <p class="field-hint">
                                Ya existe un saldo inicial registrado. Usa esto solo para corregir la caja
                                (por ejemplo, si el monto de apertura quedó mal registrado) — no edites el
                                movimiento original, queda un nuevo registro para conservar la trazabilidad.
                            </p>
                        @endif
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Monto <span class="req">*</span></label>
                                <input type="number" name="monto" min="0.01" step="0.01"
                                       value="{{ old('monto') }}" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha <span class="req">*</span></label>
                                <input type="text" name="fecha" data-nova-date placeholder="dd/mm/aaaa" class="form-input">
                            </div>
                            <div class="form-group span-2">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-input" rows="2"></textarea>
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

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
