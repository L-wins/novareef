@extends('layouts.app')

@section('titulo', 'Editar — ' . $arbitro->usuario->nombreUsuario)
@section('seccion', 'Árbitros')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver al detalle
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Editar árbitro</h1>
            <p class="page-subheading" style="font-family:'Cascadia Code','SF Mono',monospace;font-size:0.8rem;">
                {{ $arbitro->codigoCarnet }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('arbitros.update', $arbitro->idArbitro) }}" novalidate id="arbitro-form">
        @csrf
        @method('PUT')

        <div class="form-card">

            {{-- Sección 1: Datos personales --}}
            <div class="form-section">
                <p class="form-section-title">Datos personales</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group span-2">
                        <label for="nombreUsuario" class="form-label">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="nombreUsuario" name="nombreUsuario"
                               value="{{ old('nombreUsuario', $arbitro->usuario->nombreUsuario) }}" maxlength="150"
                               placeholder="Ej. Juan Carlos Pérez"
                               class="form-input {{ $errors->has('nombreUsuario') ? 'is-invalid' : '' }}">
                        @error('nombreUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="emailUsuario" class="form-label">Correo electrónico <span class="req">*</span></label>
                        <input type="email" id="emailUsuario" name="emailUsuario"
                               value="{{ old('emailUsuario', $arbitro->usuario->emailUsuario) }}"
                               placeholder="arbitro@ejemplo.com"
                               class="form-input {{ $errors->has('emailUsuario') ? 'is-invalid' : '' }}">
                        @error('emailUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefonoUsuario" class="form-label">Teléfono</label>
                        <input type="text" id="telefonoUsuario" name="telefonoUsuario"
                               value="{{ old('telefonoUsuario', $arbitro->usuario->telefonoUsuario) }}" maxlength="20"
                               placeholder="Ej. 3001234567"
                               class="form-input {{ $errors->has('telefonoUsuario') ? 'is-invalid' : '' }}">
                        @error('telefonoUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="passwordUsuario" class="form-label">Nueva contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="passwordUsuario" name="passwordUsuario"
                                   placeholder="Dejar en blanco para no cambiar"
                                   class="form-input {{ $errors->has('passwordUsuario') ? 'is-invalid' : '' }}">
                            <button type="button" class="toggle-pwd" data-target="passwordUsuario" aria-label="Mostrar contraseña">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('passwordUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="passwordUsuario_confirmation" class="form-label">Confirmar contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="passwordUsuario_confirmation" name="passwordUsuario_confirmation"
                                   placeholder="Repite la nueva contraseña" class="form-input">
                            <button type="button" class="toggle-pwd" data-target="passwordUsuario_confirmation" aria-label="Mostrar contraseña">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <p id="pwd-match-msg" class="field-error" style="display:none;">Las contraseñas no coinciden.</p>
                    </div>

                </div>
            </div>

            {{-- Sección 2: Identificación --}}
            <div class="form-section">
                <p class="form-section-title">Identificación</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="idCategoria" class="form-label">Categoría <span class="req">*</span></label>
                        <select id="idCategoria" name="idCategoria"
                                data-nova-select data-searchable="true" data-placeholder="Selecciona una categoría"
                                class="form-select {{ $errors->has('idCategoria') ? 'is-invalid' : '' }}">
                            <option value="">— Selecciona —</option>
                            @php $catSel = (int) old('idCategoria', $arbitro->idCategoria); @endphp
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->idCategoria }}"
                                    {{ $catSel === $categoria->idCategoria ? 'selected' : '' }}>
                                    {{ $categoria->nombreCategoria }}
                                </option>
                            @endforeach
                        </select>
                        @error('idCategoria') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="numeroDocumento" class="form-label">Número de documento <span class="req">*</span></label>
                        <input type="text" id="numeroDocumento" name="numeroDocumento"
                               value="{{ old('numeroDocumento', $arbitro->numeroDocumento) }}" maxlength="30"
                               placeholder="Ej. 1234567890"
                               class="form-input {{ $errors->has('numeroDocumento') ? 'is-invalid' : '' }}">
                        @error('numeroDocumento') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tipoDocumento" class="form-label">Tipo de documento <span class="req">*</span></label>
                        <select id="tipoDocumento" name="tipoDocumento"
                                data-nova-select data-placeholder="Tipo de documento"
                                class="form-select {{ $errors->has('tipoDocumento') ? 'is-invalid' : '' }}">
                            @php $tipo = old('tipoDocumento', $arbitro->tipoDocumento); @endphp
                            <option value="cedula"      {{ $tipo === 'cedula'      ? 'selected' : '' }}>Cédula</option>
                            <option value="pasaporte"   {{ $tipo === 'pasaporte'   ? 'selected' : '' }}>Pasaporte</option>
                            <option value="extranjeria" {{ $tipo === 'extranjeria' ? 'selected' : '' }}>Extranjería</option>
                        </select>
                        @error('tipoDocumento') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="lugarExpedicionCC" class="form-label">Lugar de expedición</label>
                        <input type="text" id="lugarExpedicionCC" name="lugarExpedicionCC"
                               value="{{ old('lugarExpedicionCC', $arbitro->lugarExpedicionCC) }}" maxlength="100"
                               placeholder="Ej. Bogotá"
                               class="form-input {{ $errors->has('lugarExpedicionCC') ? 'is-invalid' : '' }}">
                        @error('lugarExpedicionCC') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 3: Información física y de salud --}}
            <div class="form-section">
                <p class="form-section-title">Información física y de salud</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="pesoArbitro" class="form-label">Peso (kg)</label>
                        <input type="number" id="pesoArbitro" name="pesoArbitro" step="0.01" min="30" max="200"
                               value="{{ old('pesoArbitro', $arbitro->pesoArbitro) }}" placeholder="Ej. 75.5"
                               class="form-input {{ $errors->has('pesoArbitro') ? 'is-invalid' : '' }}">
                        @error('pesoArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="estaturaArbitro" class="form-label">Estatura (m)</label>
                        <input type="number" id="estaturaArbitro" name="estaturaArbitro" step="0.01" min="1.00" max="2.50"
                               value="{{ old('estaturaArbitro', $arbitro->estaturaArbitro) }}" placeholder="Ej. 1.78"
                               class="form-input {{ $errors->has('estaturaArbitro') ? 'is-invalid' : '' }}">
                        @error('estaturaArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="rhArbitro" class="form-label">RH</label>
                        <input type="text" id="rhArbitro" name="rhArbitro"
                               value="{{ old('rhArbitro', $arbitro->rhArbitro) }}" maxlength="5" placeholder="Ej. O+"
                               class="form-input {{ $errors->has('rhArbitro') ? 'is-invalid' : '' }}">
                        @error('rhArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="epsArbitro" class="form-label">EPS</label>
                        <input type="text" id="epsArbitro" name="epsArbitro"
                               value="{{ old('epsArbitro', $arbitro->epsArbitro) }}" maxlength="100" placeholder="Ej. Sura"
                               class="form-input {{ $errors->has('epsArbitro') ? 'is-invalid' : '' }}">
                        @error('epsArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 4: Información profesional y administrativa --}}
            <div class="form-section">
                <p class="form-section-title">Información profesional y administrativa</p>
                <div class="form-grid form-grid-2">

                    <div class="form-group">
                        <label for="profesionArbitro" class="form-label">Profesión</label>
                        <input type="text" id="profesionArbitro" name="profesionArbitro"
                               value="{{ old('profesionArbitro', $arbitro->profesionArbitro) }}" maxlength="100"
                               placeholder="Ej. Ingeniero"
                               class="form-input {{ $errors->has('profesionArbitro') ? 'is-invalid' : '' }}">
                        @error('profesionArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="fechaIngresoColegio" class="form-label">Fecha de ingreso al colegio</label>
                        <input type="text" id="fechaIngresoColegio" name="fechaIngresoColegio"
                               data-nova-date placeholder="dd/mm/aaaa"
                               value="{{ old('fechaIngresoColegio', optional($arbitro->fechaIngresoColegio)->format('Y-m-d')) }}"
                               class="form-input {{ $errors->has('fechaIngresoColegio') ? 'is-invalid' : '' }}">
                        @error('fechaIngresoColegio') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group span-2">
                        <label for="direccionArbitro" class="form-label">Dirección</label>
                        <input type="text" id="direccionArbitro" name="direccionArbitro"
                               value="{{ old('direccionArbitro', $arbitro->direccionArbitro) }}" maxlength="255"
                               placeholder="Calle, número, complemento…"
                               class="form-input {{ $errors->has('direccionArbitro') ? 'is-invalid' : '' }}">
                        @error('direccionArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="barrioArbitro" class="form-label">Barrio</label>
                        <input type="text" id="barrioArbitro" name="barrioArbitro"
                               value="{{ old('barrioArbitro', $arbitro->barrioArbitro) }}" maxlength="100"
                               placeholder="Ej. Chapinero"
                               class="form-input {{ $errors->has('barrioArbitro') ? 'is-invalid' : '' }}">
                        @error('barrioArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Sección 5: Vehículo --}}
            <div class="form-section">
                <p class="form-section-title">Vehículo</p>

                @php $tieneVehiculo = (bool) old('tieneVehiculo', $arbitro->tieneVehiculo); @endphp

                <div class="form-check" style="margin-bottom:1rem;">
                    <input type="checkbox" id="tieneVehiculo" name="tieneVehiculo" value="1"
                           class="form-check-input" {{ $tieneVehiculo ? 'checked' : '' }}>
                    <label for="tieneVehiculo" class="form-check-label">El árbitro tiene vehículo</label>
                </div>

                <div id="vehiculo-fields" class="form-grid form-grid-2"
                     style="{{ $tieneVehiculo ? '' : 'display:none' }}">

                    <div class="form-group">
                        <label for="tipoVehiculo" class="form-label">Tipo de vehículo</label>
                        <select id="tipoVehiculo" name="tipoVehiculo"
                                data-nova-select data-placeholder="Tipo de vehículo"
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
                               value="{{ old('marcaVehiculo', $arbitro->marcaVehiculo) }}" maxlength="50" placeholder="Ej. Mazda"
                               class="form-input {{ $errors->has('marcaVehiculo') ? 'is-invalid' : '' }}">
                        @error('marcaVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="placaVehiculo" class="form-label">Placa</label>
                        <input type="text" id="placaVehiculo" name="placaVehiculo"
                               value="{{ old('placaVehiculo', $arbitro->placaVehiculo) }}" maxlength="20" placeholder="Ej. ABC123"
                               class="form-input {{ $errors->has('placaVehiculo') ? 'is-invalid' : '' }}">
                        @error('placaVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="colorVehiculo" class="form-label">Color</label>
                        <input type="text" id="colorVehiculo" name="colorVehiculo"
                               value="{{ old('colorVehiculo', $arbitro->colorVehiculo) }}" maxlength="30" placeholder="Ej. Rojo"
                               class="form-input {{ $errors->has('colorVehiculo') ? 'is-invalid' : '' }}">
                        @error('colorVehiculo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('arbitros.show', $arbitro->idArbitro) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Guardar cambios
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/arbitros/arbitros.js'])
@endpush
