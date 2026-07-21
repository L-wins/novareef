
@extends('layouts.app')

@section('titulo', 'Mi perfil')
@section('seccion', 'Mi perfil')

@push('styles')
    @vite(['resources/css/arbitros/arbitros.css'])
@endpush

@section('contenido')
@php
    $porcentaje = $arbitro->porcentajePerfil;
    $colorBar   = $arbitro->colorPerfil;
    $estadoObj  = $arbitro->estado;
@endphp

<div class="container" style="max-width:900px;">

    @if (session('success'))
        <div id="flash-msg" class="flash-success">{{ session('success') }}</div>
    @elseif (session('error'))
        <div id="flash-msg" class="flash-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash-error">
            <strong>Corrige los siguientes errores:</strong>
            <ul style="margin:.4rem 0 0 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== HERO ===== --}}
    <div class="profile-hero">
        <div class="profile-hero-left">

            <div class="profile-photo-wrap">
                @if ($arbitro->fotoPerfil)
                    <img src="{{ asset('storage/' . $arbitro->fotoPerfil) }}"
                         alt="{{ $arbitro->usuario->nombreUsuario }}"
                         class="profile-photo">
                @else
                    <div class="profile-photo profile-photo-initials">
                        {{ strtoupper(substr($arbitro->usuario->nombreUsuario, 0, 2)) }}
                    </div>
                @endif

                <form method="POST" action="{{ route('arbitros.foto.subir', $arbitro->idArbitro) }}"
                      enctype="multipart/form-data" class="profile-photo-form">
                    @csrf
                    <label for="input-foto" class="profile-photo-overlay" title="Cambiar foto">
                        <i class="fa-solid fa-camera"></i>
                        <span>Cambiar foto</span>
                    </label>
                    <input type="file" id="input-foto" name="foto"
                           accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
                           style="display:none;">
                </form>

                @if ($arbitro->fotoPerfil)
                    <form method="POST" action="{{ route('arbitros.foto.eliminar', $arbitro->idArbitro) }}"
                          class="profile-photo-delete"
                          onsubmit="return confirm('¿Eliminar tu foto de perfil?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon-delete" title="Eliminar foto">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>
                @endif
            </div>

            <div class="profile-hero-info">
                <h1 class="profile-hero-name">{{ $arbitro->usuario->nombreUsuario }}</h1>
                <div class="profile-hero-meta">
                    <span class="cat-badge">{{ $arbitro->categoria->nombreCategoria }}</span>
                    <span class="estado-pill" data-color="{{ $estadoObj->color ?? 'gray' }}">
                        {{ $estadoObj->etiqueta ?? ucfirst(str_replace('_', ' ', $arbitro->estadoArbitro)) }}
                    </span>
                    <span class="td-code" style="font-size:0.78rem;">{{ $arbitro->codigoCarnet }}</span>
                </div>

                <div class="profile-progress">
                    <div class="profile-progress-head">
                        <span class="profile-progress-label">Perfil completo</span>
                        <span class="profile-progress-value" data-color="{{ $colorBar }}">{{ $porcentaje }}%</span>
                    </div>
                    <div class="profile-progress-bar">
                        <div class="profile-progress-fill" data-color="{{ $colorBar }}"
                             style="width: {{ $porcentaje }}%;"></div>
                    </div>
                    @if ($porcentaje < 100)
                        <p class="profile-progress-hint">
                            Completa los campos faltantes para llegar al 100%.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        <a href="{{ route('arbitros.estado-cuenta') }}" class="btn btn-secondary">
            <i class="fa-solid fa-sack-dollar"></i>
            Mi estado de cuenta
            @if ($saldoPendienteCobrar > 0)
                <span class="badge badge-amber">${{ number_format($saldoPendienteCobrar, 0, ',', '.') }} pendiente</span>
            @endif
        </a>
    </div>

    {{-- ===== DATOS NO EDITABLES ===== --}}
    <div class="detail-card" style="margin-top:1rem;">
        <p class="detail-section-title">Datos personales (no editables)</p>
        <div class="detail-grid">
            <div class="detail-field">
                <span class="detail-label">Nombre completo</span>
                <span class="detail-value">{{ $arbitro->usuario->nombreUsuario }}</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Correo electrónico</span>
                <span class="detail-value">{{ $arbitro->usuario->emailUsuario }}</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Documento</span>
                <span class="detail-value">
                    {{ ucfirst($arbitro->tipoDocumento) }} — {{ $arbitro->numeroDocumento }}
                </span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Categoría</span>
                <span class="detail-value">{{ $arbitro->categoria->nombreCategoria }}</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Fecha de ingreso</span>
                <span class="detail-value">
                    {{ $arbitro->fechaIngresoColegio ? $arbitro->fechaIngresoColegio->format('d/m/Y') : '—' }}
                </span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Código de carné</span>
                <span class="detail-value td-code">{{ $arbitro->codigoCarnet }}</span>
            </div>
        </div>
        <p class="detail-empty" style="margin-top:1rem;">
            Si necesitas modificar alguno de estos datos, contacta a tu colegio.
        </p>
    </div>

    {{-- ===== FORMULARIO EDITABLE ===== --}}
    <form method="POST" action="{{ route('arbitros.mi-perfil.update') }}" id="arbitro-form" novalidate
          style="margin-top:1rem;">
        @csrf
        @method('PUT')

        <div class="form-card">

            {{-- Contacto --}}
            <div class="form-section">
                <p class="form-section-title">Contacto</p>
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label for="telefonoUsuario" class="form-label">Teléfono</label>
                        <input type="text" id="telefonoUsuario" name="telefonoUsuario"
                               value="{{ old('telefonoUsuario', $arbitro->usuario->telefonoUsuario) }}"
                               maxlength="20" placeholder="Ej. 3001234567"
                               class="form-input {{ $errors->has('telefonoUsuario') ? 'is-invalid' : '' }}">
                        @error('telefonoUsuario') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="epsArbitro" class="form-label">EPS</label>
                        <input type="text" id="epsArbitro" name="epsArbitro"
                               value="{{ old('epsArbitro', $arbitro->epsArbitro) }}"
                               maxlength="100" placeholder="Ej. Sura"
                               class="form-input {{ $errors->has('epsArbitro') ? 'is-invalid' : '' }}">
                        @error('epsArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                @unless($yaAceptoDatosSensibles ?? false)
                <div class="form-check" style="margin-top:0.75rem;">
                    <input type="checkbox" id="consentimientoDatosSensibles" name="consentimientoDatosSensibles" value="1"
                           class="form-check-input"
                           {{ old('consentimientoDatosSensibles') ? 'checked' : '' }}>
                    <label for="consentimientoDatosSensibles" class="form-check-label">
                        Autorizo el tratamiento de mis datos de salud (RH, EPS) — son opcionales.
                    </label>
                </div>
                @error('consentimientoDatosSensibles') <p class="field-error">{{ $message }}</p> @enderror
                @endunless
            </div>

            {{-- Datos físicos --}}
            <div class="form-section">
                <p class="form-section-title">Datos físicos</p>
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label for="pesoArbitro" class="form-label">Peso (kg)</label>
                        <input type="number" id="pesoArbitro" name="pesoArbitro"
                               step="0.1" min="30" max="200"
                               value="{{ old('pesoArbitro', $arbitro->pesoArbitro) }}"
                               class="form-input {{ $errors->has('pesoArbitro') ? 'is-invalid' : '' }}">
                        @error('pesoArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="estaturaArbitro" class="form-label">Estatura (m)</label>
                        <input type="number" id="estaturaArbitro" name="estaturaArbitro"
                               step="0.01" min="1.00" max="2.50"
                               value="{{ old('estaturaArbitro', $arbitro->estaturaArbitro) }}"
                               class="form-input {{ $errors->has('estaturaArbitro') ? 'is-invalid' : '' }}">
                        @error('estaturaArbitro') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Ubicación --}}
            <div class="form-section">
                <p class="form-section-title">Ubicación</p>
                <div class="form-grid form-grid-2">
                    <div class="form-group span-2">
                        <label for="direccionArbitro" class="form-label">Dirección</label>
                        <input type="text" id="direccionArbitro" name="direccionArbitro"
                               value="{{ old('direccionArbitro', $arbitro->direccionArbitro) }}"
                               maxlength="255" placeholder="Calle, número, complemento..."
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
                </div>
            </div>

            {{-- Vehículo --}}
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
                @if ($porcentaje < 100)
                    <a href="{{ route('arbitros.completar-perfil') }}" class="btn btn-secondary">
                        Completar perfil
                    </a>
                @endif
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
