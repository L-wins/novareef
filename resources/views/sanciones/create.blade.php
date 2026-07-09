@extends('layouts.app')

@section('titulo', 'Nueva sanción')
@section('seccion', 'Sanciones')

@push('styles')
    @vite(['resources/css/sanciones/sanciones.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('sanciones.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a sanciones
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Nueva sanción</h1>
            <p class="page-subheading">Registra una sanción disciplinaria a un árbitro del colegio.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">Revisa los campos marcados abajo.</div>
    @endif

    @if ($tipos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-triangle-exclamation" style="font-size:40px;"></i>
            <p>Tu colegio aún no tiene tipos de sanción configurados.</p>
            @can('editar-sanciones')
                <a href="{{ route('tipos-sancion.index') }}" class="btn btn-primary" style="margin-top:1rem;">
                    Configurar tipos de sanción
                </a>
            @endcan
        </div>
    @else
    <form method="POST" action="{{ route('sanciones.store') }}" class="form-card" novalidate>
        @csrf

        <div class="form-section">
            <p class="form-section-title">Árbitro y tipo</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="idArbitro">Árbitro <span class="req">*</span></label>
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

                <div class="form-group">
                    <label class="form-label" for="idTipoSancion">Tipo de sanción <span class="req">*</span></label>
                    <select id="idTipoSancion" name="idTipoSancion" data-nova-select data-placeholder="Selecciona un tipo"
                            class="form-select {{ $errors->has('idTipoSancion') ? 'is-invalid' : '' }}">
                        <option value="">— Selecciona —</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->idTipoSancion }}" {{ (string) old('idTipoSancion') === (string) $tipo->idTipoSancion ? 'selected' : '' }}>
                                {{ $tipo->etiqueta }} ({{ ucfirst($tipo->severidad) }})
                            </option>
                        @endforeach
                    </select>
                    @error('idTipoSancion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="form-section">
            <p class="form-section-title">Detalle del hecho</p>
            <div class="form-grid form-grid-2">
                <div class="form-group span-2">
                    <label class="form-label" for="motivoSancion">Motivo <span class="req">*</span></label>
                    <textarea id="motivoSancion" name="motivoSancion" class="form-textarea {{ $errors->has('motivoSancion') ? 'is-invalid' : '' }}">{{ old('motivoSancion') }}</textarea>
                    @error('motivoSancion') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fechaHecho">Fecha del hecho <span class="req">*</span></label>
                    <input type="text" id="fechaHecho" name="fechaHecho" value="{{ old('fechaHecho') }}"
                           data-nova-date placeholder="dd/mm/aaaa" class="form-input {{ $errors->has('fechaHecho') ? 'is-invalid' : '' }}">
                    @error('fechaHecho') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fechaInicioSancion">Inicio de la sanción <span class="req">*</span></label>
                    <input type="text" id="fechaInicioSancion" name="fechaInicioSancion" value="{{ old('fechaInicioSancion') }}"
                           data-nova-date placeholder="dd/mm/aaaa" class="form-input {{ $errors->has('fechaInicioSancion') ? 'is-invalid' : '' }}">
                    @error('fechaInicioSancion') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fechaFinSancion">Fin de la sanción</label>
                    <input type="text" id="fechaFinSancion" name="fechaFinSancion" value="{{ old('fechaFinSancion') }}"
                           data-nova-date placeholder="dd/mm/aaaa (opcional)" class="form-input {{ $errors->has('fechaFinSancion') ? 'is-invalid' : '' }}">
                    <p class="field-hint">Déjalo vacío si la sanción es indefinida hasta que el Comité la resuelva.</p>
                    @error('fechaFinSancion') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="form-section" style="border-bottom:none;">
            <p class="form-section-title">Multa económica (opcional)</p>
            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label" style="flex-direction:row;align-items:center;gap:0.5rem;">
                        <input type="checkbox" id="tieneMultaEconomica" name="tieneMultaEconomica" value="1" {{ old('tieneMultaEconomica') ? 'checked' : '' }}>
                        Esta sanción lleva una multa económica
                    </label>
                </div>
                <div class="form-group" data-campo-condicional="multa" style="{{ old('tieneMultaEconomica') ? 'display:flex;' : '' }}">
                    <label class="form-label" for="montoMulta">Valor de la multa</label>
                    <input type="number" id="montoMulta" name="montoMulta" value="{{ old('montoMulta') }}"
                           min="0.01" step="0.01" class="form-input {{ $errors->has('montoMulta') ? 'is-invalid' : '' }}">
                    @error('montoMulta') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <a href="{{ route('sanciones.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Registrar sanción
            </button>
        </div>
    </form>
    @endif

</div>
@endsection

@push('scripts')
    @vite(['resources/js/sanciones/sanciones.js'])
@endpush
