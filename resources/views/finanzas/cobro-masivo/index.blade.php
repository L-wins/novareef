@extends('layouts.app')

@section('titulo', 'Cobro masivo')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.balance.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Cobro masivo</h1>
            <p class="page-subheading">Registra el mismo cargo (mensualidad, indumentaria, etc.) a varios árbitros de una sola vez.</p>
        </div>
    </div>

    @include('finanzas.partials.subnav')

    @if ($errors->any())
        <div class="flash-error">
            Revisa los campos marcados abajo.
        </div>
    @endif
    @if (session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('finanzas.cobro-masivo.store') }}" class="form-card" id="form-cobro-masivo" novalidate data-confirm-submit
          data-confirm-title="¿Registrar cobro masivo?"
          data-confirm-text="Se creará un movimiento por cada árbitro seleccionado."
          data-confirm-btn="Sí, registrar">
        @csrf

        @php
            $cmTieneErroresCargo = $errors->hasAny(['categoria', 'fechaMovimiento', 'concepto', 'montoTotal']);
        @endphp
        <details class="form-section cm-datos-cargo" id="cm-datos-cargo" {{ $cmTieneErroresCargo ? 'open' : '' }}>
            <summary class="cm-datos-cargo__summary">
                <span class="form-section-title" style="margin-bottom:0;">Datos del cargo</span>
                <span class="cm-datos-cargo__resumen" data-cm-resumen-cargo>Sin definir aún</span>
                <i class="fa-solid fa-chevron-down cm-datos-cargo__chevron"></i>
            </summary>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="categoria">Categoría <span class="req">*</span></label>
                    <select id="categoria" name="categoria" data-nova-select
                            class="form-select {{ $errors->has('categoria') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($categorias as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('categoria') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fechaMovimiento">Fecha <span class="req">*</span></label>
                    <input type="text" id="fechaMovimiento" name="fechaMovimiento" value="{{ old('fechaMovimiento') }}"
                           data-nova-date placeholder="dd/mm/aaaa"
                           class="form-input {{ $errors->has('fechaMovimiento') ? 'is-invalid' : '' }}">
                    @error('fechaMovimiento') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group span-2">
                    <label class="form-label" for="concepto">Concepto <span class="req">*</span></label>
                    <input type="text" id="concepto" name="concepto" value="{{ old('concepto') }}"
                           maxlength="255" placeholder="Ej. Mensualidad julio 2026"
                           class="form-input {{ $errors->has('concepto') ? 'is-invalid' : '' }}">
                    @error('concepto') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="montoTotal">Monto por defecto <span class="req">*</span></label>
                    <input type="number" id="montoTotal" name="montoTotal" value="{{ old('montoTotal') }}"
                           min="0.01" step="0.01" placeholder="0.00"
                           class="form-input {{ $errors->has('montoTotal') ? 'is-invalid' : '' }}">
                    @error('montoTotal') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="form-hint">Se precarga en cada árbitro — se puede ajustar fila por fila.</p>
                </div>

                <div class="form-group span-2">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-textarea">{{ old('observaciones') }}</textarea>
                </div>
            </div>
        </details>

        <div class="form-section">
            <p class="form-section-title">Árbitros ({{ $arbitros->count() }})</p>

            <div class="cm-toolbar">
                <input type="search" data-cm-filtro placeholder="Buscar árbitro…" class="form-input cm-toolbar__buscar" autocomplete="off">
                <span data-cm-contador class="cm-toolbar__contador">0 de {{ $arbitros->count() }} seleccionados</span>
                <button type="button" data-cm-seleccionar-visibles class="btn btn-secondary btn-sm">Seleccionar visibles</button>
                <button type="button" data-cm-quitar-seleccion class="btn btn-secondary btn-sm">Quitar selección</button>
            </div>

            @if ($errors->has('cargos'))
                <p class="field-error">{{ $errors->first('cargos') }}</p>
            @endif

            <div class="table-card table-scroll">
                <table class="data-table cm-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Árbitro</th>
                            <th>Monto</th>
                            <th>¿Ya pagó?</th>
                            <th>Datos del pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($arbitros as $i => $arbitro)
                            @php $nombre = $arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $arbitro->idArbitro; @endphp
                            <tr data-cm-fila data-nombre="{{ mb_strtolower($nombre) }}">
                                <td>
                                    <input type="hidden" name="cargos[{{ $i }}][idArbitro]" value="{{ $arbitro->idArbitro }}">
                                    <input type="checkbox" name="cargos[{{ $i }}][incluir]" value="1" data-cm-incluir>
                                </td>
                                <td class="td-primary">{{ $nombre }}</td>
                                <td>
                                    <input type="number" name="cargos[{{ $i }}][monto]" min="0.01" step="0.01"
                                           data-cm-monto class="form-input form-input-sm">
                                </td>
                                <td>
                                    <input type="checkbox" name="cargos[{{ $i }}][yaPago]" value="1" data-cm-yapago>
                                </td>
                                <td>
                                    <div class="cm-pago-fields" data-cm-pago-fields>
                                        <select name="cargos[{{ $i }}][metodoPago]" class="form-select form-select-sm">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="pago_digital">Pago digital</option>
                                        </select>
                                        <input type="date" name="cargos[{{ $i }}][fechaAbono]" class="form-input form-input-sm">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions-end form-actions-end--gap">
            <a href="{{ route('finanzas.balance.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Registrar cobro masivo
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/cobro-masivo.js'])
@endpush
