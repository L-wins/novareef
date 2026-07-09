@extends('layouts.app')

@section('titulo', 'Nuevo movimiento')
@section('seccion', 'Finanzas')

@push('styles')
    @vite(['resources/css/finanzas/finanzas.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('finanzas.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a finanzas
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nuevo movimiento financiero</h1>
            <p class="page-subheading">Registra un ingreso o egreso para el colegio.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">
            Revisa los campos marcados abajo.
        </div>
    @endif

    <form method="POST" action="{{ route('finanzas.store') }}" class="form-card" novalidate>
        @csrf

        <div class="form-section">
            <p class="form-section-title">Tipo y categoría</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="tipoMovimiento">Tipo <span class="req">*</span></label>
                    <select id="tipoMovimiento" name="tipoMovimiento" data-nova-select
                            class="form-select {{ $errors->has('tipoMovimiento') ? 'is-invalid' : '' }}">
                        <option value="ingreso" {{ old('tipoMovimiento', 'ingreso') === 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                        <option value="egreso"  {{ old('tipoMovimiento') === 'egreso' ? 'selected' : '' }}>Egreso</option>
                    </select>
                    @error('tipoMovimiento') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="categoria">Categoría <span class="req">*</span></label>
                    <select id="categoria" name="categoria" data-nova-select
                            class="form-select {{ $errors->has('categoria') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                    </select>
                    @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Detalle</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="concepto">Concepto <span class="req">*</span></label>
                    <input type="text" id="concepto" name="concepto" value="{{ old('concepto') }}"
                           maxlength="255" placeholder="Ej. Mensualidad julio 2026"
                           class="form-input {{ $errors->has('concepto') ? 'is-invalid' : '' }}">
                    @error('concepto') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="montoTotal">Monto <span class="req">*</span></label>
                    <input type="number" id="montoTotal" name="montoTotal" value="{{ old('montoTotal') }}"
                           min="0.01" step="0.01" placeholder="0.00"
                           class="form-input {{ $errors->has('montoTotal') ? 'is-invalid' : '' }}">
                    @error('montoTotal') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fechaMovimiento">Fecha <span class="req">*</span></label>
                    <input type="text" id="fechaMovimiento" name="fechaMovimiento" value="{{ old('fechaMovimiento') }}"
                           data-nova-date placeholder="dd/mm/aaaa"
                           class="form-input {{ $errors->has('fechaMovimiento') ? 'is-invalid' : '' }}">
                    @error('fechaMovimiento') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Árbitro afiliado — visible para nómina y multa --}}
                <div class="form-group" data-campo-condicional="arbitro">
                    <label class="form-label" for="idArbitro">Árbitro</label>
                    <select id="idArbitro" name="idArbitro" data-nova-select data-searchable="true" data-placeholder="Selecciona un árbitro"
                            class="form-select {{ $errors->has('idArbitro') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($arbitros as $arbitro)
                            <option value="{{ $arbitro->idArbitro }}" {{ (string) old('idArbitro') === (string) $arbitro->idArbitro ? 'selected' : '' }}>
                                {{ $arbitro->usuario->nombreUsuario ?? 'Árbitro #' . $arbitro->idArbitro }}
                            </option>
                        @endforeach
                    </select>
                    @error('idArbitro') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Árbitro externo — sin registro en el colegio --}}
                <div class="form-group" data-campo-condicional="arbitro-externo">
                    <label class="form-label" for="nombreArbitroExterno">Nombre del árbitro externo</label>
                    <input type="text" id="nombreArbitroExterno" name="nombreArbitroExterno" value="{{ old('nombreArbitroExterno') }}"
                           maxlength="150" class="form-input {{ $errors->has('nombreArbitroExterno') ? 'is-invalid' : '' }}">
                    @error('nombreArbitroExterno') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" data-campo-condicional="arbitro-externo">
                    <label class="form-label" for="documentoArbitroExterno">Documento</label>
                    <input type="text" id="documentoArbitroExterno" name="documentoArbitroExterno" value="{{ old('documentoArbitroExterno') }}"
                           maxlength="30" class="form-input {{ $errors->has('documentoArbitroExterno') ? 'is-invalid' : '' }}">
                    @error('documentoArbitroExterno') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Torneo — visible para ingreso por torneo --}}
                <div class="form-group span-2" data-campo-condicional="torneo">
                    <label class="form-label" for="idTorneo">Torneo</label>
                    <select id="idTorneo" name="idTorneo" data-nova-select data-searchable="true" data-placeholder="Selecciona un torneo"
                            class="form-select {{ $errors->has('idTorneo') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($torneos as $torneo)
                            <option value="{{ $torneo->idTorneo }}" {{ (string) old('idTorneo') === (string) $torneo->idTorneo ? 'selected' : '' }}>
                                {{ $torneo->nombreTorneo }} ({{ $torneo->temporada }})
                            </option>
                        @endforeach
                    </select>
                    @error('idTorneo') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group span-2">
                    <label class="form-label" for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-textarea">{{ old('observaciones') }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <a href="{{ route('finanzas.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Registrar movimiento
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/finanzas/finanzas.js'])
@endpush
