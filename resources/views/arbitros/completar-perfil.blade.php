@extends('layouts.app')

@section('titulo', 'Completa tu perfil')
@section('seccion', 'Mi perfil')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container" style="max-width:760px;">

    {{-- Bienvenida --}}
    <div style="text-align:center;margin-bottom:2rem;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(16,185,129,.15);
                    border:2px solid rgba(16,185,129,.3);display:flex;align-items:center;
                    justify-content:center;margin:0 auto 1rem;">
            <i class="fa-solid fa-circle-check" style="font-size:22px;color:#6ee7b7;"></i>
        </div>
        <h1 class="page-heading" style="font-size:1.35rem;margin-bottom:0.4rem;">
            Bienvenido a NovaReef
        </h1>
        <p style="font-size:0.9rem;color:var(--text-muted);max-width:480px;margin:0 auto;">
            Completa tu información para continuar. Puedes omitir los campos que no tengas disponibles ahora mismo.
        </p>
    </div>

    @if($errors->any())
        <div class="flash-error" style="margin-bottom:1.25rem;">
            <strong>Corrige los siguientes errores:</strong>
            <ul style="margin:0.4rem 0 0 1.25rem;padding:0;">
                @foreach($errors->all() as $error)
                    <li style="font-size:0.82rem;margin-top:2px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('arbitros.guardar-perfil') }}" novalidate id="arbitro-form">
        @csrf

        <div class="form-card">

            {{-- Sección 1: Datos físicos y de salud --}}
            <div class="form-section">
                <p class="form-section-title">Datos físicos y de salud</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="pesoArbitro" class="form-label">
                            Peso (kg)
                            <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;text-transform:none;"> — requerido para activar tu cuenta</span>
                        </label>
                        <input type="number" id="pesoArbitro" name="pesoArbitro"
                               step="0.1" min="30" max="200"
                               value="{{ old('pesoArbitro', $arbitro->pesoArbitro) }}"
                               placeholder="Ej. 75.5"
                               class="form-input {{ $errors->has('pesoArbitro') ? 'is-invalid' : '' }}">
                        @error('pesoArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="estaturaArbitro" class="form-label">Estatura (m)</label>
                        <input type="number" id="estaturaArbitro" name="estaturaArbitro"
                               step="0.01" min="1.00" max="2.50"
                               value="{{ old('estaturaArbitro', $arbitro->estaturaArbitro) }}"
                               placeholder="Ej. 1.78"
                               class="form-input {{ $errors->has('estaturaArbitro') ? 'is-invalid' : '' }}">
                        @error('estaturaArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="rhArbitro" class="form-label">Grupo sanguíneo (RH)</label>
                        <input type="text" id="rhArbitro" name="rhArbitro"
                               value="{{ old('rhArbitro', $arbitro->rhArbitro) }}"
                               maxlength="5" placeholder="Ej. O+"
                               class="form-input {{ $errors->has('rhArbitro') ? 'is-invalid' : '' }}">
                        @error('rhArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="epsArbitro" class="form-label">EPS</label>
                        <input type="text" id="epsArbitro" name="epsArbitro"
                               value="{{ old('epsArbitro', $arbitro->epsArbitro) }}"
                               maxlength="100" placeholder="Ej. Sura"
                               class="form-input {{ $errors->has('epsArbitro') ? 'is-invalid' : '' }}">
                        @error('epsArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="profesionArbitro" class="form-label">Profesión</label>
                        <input type="text" id="profesionArbitro" name="profesionArbitro"
                               value="{{ old('profesionArbitro', $arbitro->profesionArbitro) }}"
                               maxlength="100" placeholder="Ej. Ingeniero de sistemas"
                               class="form-input {{ $errors->has('profesionArbitro') ? 'is-invalid' : '' }}">
                        @error('profesionArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 2: Ubicación --}}
            <div class="form-section">
                <p class="form-section-title">Ubicación</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group span-2">
                        <label for="direccionArbitro" class="form-label">Dirección</label>
                        <input type="text" id="direccionArbitro" name="direccionArbitro"
                               value="{{ old('direccionArbitro', $arbitro->direccionArbitro) }}"
                               maxlength="255" placeholder="Calle, número, complemento…"
                               class="form-input {{ $errors->has('direccionArbitro') ? 'is-invalid' : '' }}">
                        @error('direccionArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="barrioArbitro" class="form-label">Barrio</label>
                        <input type="text" id="barrioArbitro" name="barrioArbitro"
                               value="{{ old('barrioArbitro', $arbitro->barrioArbitro) }}"
                               maxlength="100" placeholder="Ej. Chapinero"
                               class="form-input {{ $errors->has('barrioArbitro') ? 'is-invalid' : '' }}">
                        @error('barrioArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="lugarExpedicionCC" class="form-label">Lugar de expedición del documento</label>
                        <input type="text" id="lugarExpedicionCC" name="lugarExpedicionCC"
                               value="{{ old('lugarExpedicionCC', $arbitro->lugarExpedicionCC) }}"
                               maxlength="100" placeholder="Ej. Bogotá"
                               class="form-input {{ $errors->has('lugarExpedicionCC') ? 'is-invalid' : '' }}">
                        @error('lugarExpedicionCC') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 3: Vehículo --}}
            <div class="form-section">
                <p class="form-section-title">Vehículo</p>

                <div class="form-check" style="margin-bottom:1rem;">
                    <input type="checkbox" id="tieneVehiculo" name="tieneVehiculo" value="1"
                           class="form-check-input"
                           {{ old('tieneVehiculo', $arbitro->tieneVehiculo) ? 'checked' : '' }}>
                    <label for="tieneVehiculo" class="form-check-label">Tengo vehículo propio</label>
                </div>

                <div id="vehiculo-fields" class="form-grid form-grid-2"
                     style="{{ old('tieneVehiculo', $arbitro->tieneVehiculo) ? '' : 'display:none' }}">

                    <div class="form-group">
                        <label for="tipoVehiculo" class="form-label">Tipo</label>
                        <select id="tipoVehiculo" name="tipoVehiculo"
                                class="form-select {{ $errors->has('tipoVehiculo') ? 'is-invalid' : '' }}">
                            @php $tv = old('tipoVehiculo', $arbitro->tipoVehiculo); @endphp
                            <option value="">— Selecciona —</option>
                            <option value="carro" {{ $tv === 'carro' ? 'selected' : '' }}>Carro</option>
                            <option value="moto"  {{ $tv === 'moto'  ? 'selected' : '' }}>Moto</option>
                            <option value="ambos" {{ $tv === 'ambos' ? 'selected' : '' }}>Ambos</option>
                        </select>
                        @error('tipoVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="marcaVehiculo" class="form-label">Marca</label>
                        <input type="text" id="marcaVehiculo" name="marcaVehiculo"
                               value="{{ old('marcaVehiculo', $arbitro->marcaVehiculo) }}"
                               maxlength="50" placeholder="Ej. Mazda"
                               class="form-input {{ $errors->has('marcaVehiculo') ? 'is-invalid' : '' }}">
                        @error('marcaVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="placaVehiculo" class="form-label">Placa</label>
                        <input type="text" id="placaVehiculo" name="placaVehiculo"
                               value="{{ old('placaVehiculo', $arbitro->placaVehiculo) }}"
                               maxlength="20" placeholder="Ej. ABC123"
                               class="form-input {{ $errors->has('placaVehiculo') ? 'is-invalid' : '' }}">
                        @error('placaVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="colorVehiculo" class="form-label">Color</label>
                        <input type="text" id="colorVehiculo" name="colorVehiculo"
                               value="{{ old('colorVehiculo', $arbitro->colorVehiculo) }}"
                               maxlength="30" placeholder="Ej. Rojo"
                               class="form-input {{ $errors->has('colorVehiculo') ? 'is-invalid' : '' }}">
                        @error('colorVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary" style="min-width:180px;">
                    <i class="fa-solid fa-check"></i>
                    Guardar y continuar
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
