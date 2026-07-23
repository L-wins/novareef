@extends('layouts.app')

@section('titulo', 'Nuevo partido')
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container desi-shell">

    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Nuevo partido</span>
    </div>

    <div class="desi-form-hero">
        <div class="desi-form-hero__main">
            <div class="desi-form-hero__icon">
                <i class="fa-solid fa-futbol"></i>
            </div>
            <div>
                <span class="desi-hero__label">Designaciones</span>
                <h1 class="desi-hero__title">Nuevo partido</h1>
                <p class="desi-hero__sub">Programa un partido para designar árbitros.</p>
            </div>
        </div>
        <a href="{{ route('designaciones.index') }}" class="btn btn-ghost desi-action-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('designaciones.store') }}" id="form-partido">
        @csrf

        <div class="form-card-grid">

            {{-- Columna 1 --}}
            <div class="form-col desi-form-panel">
                <div class="desi-form-panel__header">
                    <span class="desi-form-panel__icon"><i class="fa-solid fa-trophy"></i></span>
                    <div>
                        <h2>Competencia</h2>
                        <p>Torneo, división, sede y formato arbitral.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Torneo <span class="req">*</span></label>
                    <select name="idTorneo" id="sel-torneo" class="form-input"
                            data-nova-select data-searchable="true" data-placeholder="Selecciona un torneo" required>
                        <option value="">Selecciona un torneo</option>
                        @foreach($torneos as $t)
                        <option value="{{ $t->idTorneo }}" {{ old('idTorneo') == $t->idTorneo ? 'selected' : '' }}>
                            {{ $t->nombreTorneo }} ({{ $t->temporada }})
                        </option>
                        @endforeach
                    </select>
                    @error('idTorneo')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">División <span class="req">*</span></label>
                    <select name="idDivision" id="sel-division" class="form-input"
                            data-nova-select data-placeholder="— Selecciona torneo primero —" required>
                        <option value="">— Selecciona torneo primero —</option>
                    </select>
                    @error('idDivision')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Sede <span class="req">*</span></label>
                    <select name="idSede" id="sel-sede" class="form-input"
                            data-nova-select data-placeholder="— Selecciona torneo primero —" required>
                        <option value="">— Selecciona torneo primero —</option>
                    </select>
                    @error('idSede')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Formato de árbitros <span class="req">*</span></label>
                    <select name="idFormato" id="sel-formato" class="form-input"
                            data-nova-select data-placeholder="Selecciona formato" required>
                        <option value="">Selecciona formato</option>
                        @foreach($formatos as $f)
                        <option value="{{ $f->idFormato }}"
                                data-arbitros="{{ $f->maxArbitros }}"
                                {{ old('idFormato') == $f->idFormato ? 'selected' : '' }}>
                            {{ $f->nombre }} ({{ $f->maxArbitros }} árbitro{{ $f->maxArbitros > 1 ? 's' : '' }})
                        </option>
                        @endforeach
                    </select>
                    @error('idFormato')<div class="form-error">{{ $message }}</div>@enderror
                </div>

            </div>

            {{-- Columna 2 --}}
            <div class="form-col desi-form-panel">
                <div class="desi-form-panel__header">
                    <span class="desi-form-panel__icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <div>
                        <h2>Programación</h2>
                        <p>Equipos, fecha, hora y observaciones.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Equipo local <span class="req">*</span></label>
                    <input type="text" name="equipoLocal" class="form-input"
                           value="{{ old('equipoLocal') }}" placeholder="Nombre del equipo local" required maxlength="100">
                    @error('equipoLocal')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Equipo visitante <span class="req">*</span></label>
                    <input type="text" name="equipoVisitante" class="form-input"
                           value="{{ old('equipoVisitante') }}" placeholder="Nombre del equipo visitante" required maxlength="100">
                    @error('equipoVisitante')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label class="form-label">Fecha <span class="req">*</span></label>
                        <input type="text" name="fechaPartido" class="form-input" data-nova-date
                               value="{{ old('fechaPartido') }}" placeholder="dd/mm/aaaa" required>
                        @error('fechaPartido')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora <span class="req">*</span></label>
                        <input type="text" name="horaPartido" class="form-input" data-nova-date
                               value="{{ old('horaPartido') }}" placeholder="HH:MM" required
                               data-enable-time="true" data-no-calendar="true" data-date-format="H:i" data-alt-format="H:i">
                        @error('horaPartido')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-input textarea-fixed" rows="3"
                              maxlength="1000" placeholder="Notas adicionales sobre el partido..."
                              id="obs-textarea">{{ old('observaciones') }}</textarea>
                    <div class="form-hint text-right"><span id="obs-counter">0</span>/1000</div>
                </div>

            </div>
        </div>

        {{-- Preview roles --}}
        <div id="formato-preview" class="formato-preview" style="display:none">
            <div class="formato-preview__title">
                <i class="fa-solid fa-users"></i>
                Roles que necesitará este partido
            </div>
            <div id="formato-roles" class="formato-roles-list"></div>
        </div>

        <div class="form-actions desi-form-actions">
            <a href="{{ route('designaciones.index') }}" class="btn btn-ghost desi-action-btn">
                <i class="fa-solid fa-xmark"></i>
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary desi-action-btn">
                <i class="fa-solid fa-futbol"></i>
                Crear partido
            </button>
        </div>
    </form>
</div>

<script>
window.urlDivisiones = "{{ url('/api/torneos') }}";
window.urlSedes      = "{{ url('/api/torneos') }}";
window.csrfToken     = "{{ csrf_token() }}";
</script>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
