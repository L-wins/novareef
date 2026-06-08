@extends('layouts.app')

@section('titulo', 'Perfil — ' . $torneo->nombreTorneo)
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver al torneo
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">{{ $torneo->nombreTorneo }}</h1>
            <p class="page-subheading">Perfil del torneo · Paso 2 de 2</p>
        </div>
        <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-primary">
            Ir al torneo
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    @if ($errors->any())
        <div class="form-note form-note--warn" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <ul style="margin:0 0 0 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ═
         SECCIÓN 1 — DIVISIONES
         ═ --}}
    <div class="perfil-step is-open" id="step-divisiones">
        <div class="perfil-step-head">
            <h3>
                <span class="perfil-step-num">1</span>
                Divisiones
                <span class="perfil-step-count">{{ $torneo->divisiones->count() }}</span>
            </h3>
            <i class="fa-solid fa-chevron-down perfil-step-toggle"></i>
        </div>
        <div class="perfil-step-body">

            @if ($torneo->divisiones->isEmpty())
                <div class="detail-empty">Aún no has agregado divisiones a este torneo.</div>
            @else
                <div class="perfil-list">
                    @foreach ($torneo->divisiones as $div)
                        <div class="perfil-list-item">
                            <div class="perfil-list-info">
                                <strong>{{ $div->nombreDivision }}</strong>
                                @if ($div->descripcion)
                                    <small>{{ $div->descripcion }}</small>
                                @endif
                                <small>{{ $div->tarifas->count() }} tarifa{{ $div->tarifas->count() === 1 ? '' : 's' }}</small>
                            </div>
                            <div class="table-actions">
                                <button type="button"
                                        class="btn-icon"
                                        title="Editar división"
                                        data-open-modal="editar-division"
                                        data-div-nombre="{{ $div->nombreDivision }}"
                                        data-div-descripcion="{{ $div->descripcion ?? '' }}"
                                        data-div-action="{{ route('divisiones.update', $div->idDivision) }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button"
                                        class="btn-icon btn-icon-danger"
                                        title="Eliminar división"
                                        data-delete-form="form-del-div-{{ $div->idDivision }}"
                                        data-confirm-title="¿Eliminar división?"
                                        data-confirm-text="Se eliminará la división «{{ $div->nombreDivision }}» y todas sus tarifas. Si tiene partidos registrados, no podrá eliminarse.">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <form id="form-del-div-{{ $div->idDivision }}" method="POST"
                                  action="{{ route('divisiones.destroy', $div->idDivision) }}" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('divisiones.store', $torneo->idTorneo) }}" class="perfil-add-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre de la división <span class="req">*</span></label>
                    <input type="text" name="nombreDivision" maxlength="100" required
                           class="form-input" placeholder="Ej. Mayores · A · Veteranos">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción (opcional)</label>
                    <input type="text" name="descripcion" class="form-input"
                           placeholder="Descripción corta">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i>
                    Agregar
                </button>
            </form>

        </div>
    </div>

    {{-- ═
         SECCIÓN 2 — SEDES
         ═ --}}
    <div class="perfil-step" id="step-sedes">
        <div class="perfil-step-head">
            <h3>
                <span class="perfil-step-num">2</span>
                Sedes
                <span class="perfil-step-count">{{ $torneo->sedes->count() }}</span>
            </h3>
            <i class="fa-solid fa-chevron-down perfil-step-toggle"></i>
        </div>
        <div class="perfil-step-body">

            @if ($torneo->sedes->isEmpty())
                <div class="detail-empty">Aún no has agregado sedes a este torneo.</div>
            @else
                <div class="perfil-list">
                    @foreach ($torneo->sedes as $sede)
                        <div class="perfil-list-item">
                            <div class="perfil-list-info">
                                <strong>{{ $sede->nombreSede }}</strong>
                                <small><i class="fa-solid fa-location-dot"></i> {{ $sede->direccion }} · {{ $sede->municipio }}</small>
                                @if ($sede->observaciones)
                                    <small>{{ $sede->observaciones }}</small>
                                @endif
                            </div>
                            <div class="table-actions">
                                @if ($sede->urlMaps)
                                    <a href="{{ $sede->urlMaps }}" target="_blank" rel="noopener"
                                       class="btn-icon" title="Ver en Google Maps">
                                        <i class="fa-solid fa-map-location-dot"></i>
                                    </a>
                                @endif
                                <button type="button"
                                        class="btn-icon"
                                        title="Editar sede"
                                        data-open-modal="editar-sede"
                                        data-sede-nombre="{{ $sede->nombreSede }}"
                                        data-sede-direccion="{{ $sede->direccion }}"
                                        data-sede-municipio="{{ $sede->municipio }}"
                                        data-sede-departamento="{{ $sede->departamento ?? '' }}"
                                        data-sede-urlmaps="{{ $sede->urlMaps ?? '' }}"
                                        data-sede-observaciones="{{ $sede->observaciones ?? '' }}"
                                        data-sede-action="{{ route('sedes.update', $sede->idSede) }}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button"
                                        class="btn-icon btn-icon-danger"
                                        title="Eliminar sede"
                                        data-delete-form="form-del-sede-{{ $sede->idSede }}"
                                        data-confirm-title="¿Eliminar sede?"
                                        data-confirm-text="Se eliminará la sede «{{ $sede->nombreSede }}». Si tiene partidos registrados, no podrá eliminarse.">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <form id="form-del-sede-{{ $sede->idSede }}" method="POST"
                                  action="{{ route('sedes.destroy', $sede->idSede) }}" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('sedes.store', $torneo->idTorneo) }}" class="perfil-add-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre de la sede <span class="req">*</span></label>
                    <input type="text" name="nombreSede" maxlength="150" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección <span class="req">*</span></label>
                    <input type="text" name="direccion" maxlength="255" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Municipio <span class="req">*</span></label>
                    <input type="text" name="municipio" maxlength="100" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Departamento</label>
                    <input type="text" name="departamento" maxlength="100" class="form-input" placeholder="Opcional">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">URL de Google Maps</label>
                    <input type="url" name="urlMaps" maxlength="500" class="form-input"
                           placeholder="https://maps.google.com/...">
                    <small class="field-hint">Pega aquí el enlace de Google Maps de la sede.</small>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" rows="2" class="form-textarea"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i>
                    Agregar sede
                </button>
            </form>

        </div>
    </div>

    {{-- ═
         SECCIÓN 3 — TARIFAS POR DIVISIÓN
         ═ --}}
    <div class="perfil-step" id="step-tarifas">
        <div class="perfil-step-head">
            <h3>
                <span class="perfil-step-num">3</span>
                Tarifas por división
            </h3>
            <i class="fa-solid fa-chevron-down perfil-step-toggle"></i>
        </div>
        <div class="perfil-step-body">

            @if ($torneo->divisiones->isEmpty())
                <div class="detail-empty">Primero agrega al menos una división para configurar tarifas.</div>
            @else
                @foreach ($torneo->divisiones as $div)
                    <div class="tarifas-block">
                        <h4 class="tarifas-block-title">
                            <i class="fa-solid fa-layer-group" style="color:var(--t-accent);"></i>
                            {{ $div->nombreDivision }}
                        </h4>

                        @if ($div->tarifas->isEmpty())
                            <div class="detail-empty" style="margin-bottom:0.85rem;">
                                Sin tarifas configuradas en esta división.
                            </div>
                        @else
                            <table class="tarifas-table">
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th>Formato</th>
                                        <th>Valor</th>
                                        <th style="width:80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($div->tarifas as $tarifa)
                                        <tr>
                                            <td>{{ $tarifa->rol->nombre ?? '—' }}</td>
                                            <td>{{ $tarifa->formato->nombre ?? '—' }}</td>
                                            <td class="tarifa-monto">$ {{ number_format((float) $tarifa->valorPago, 0, ',', '.') }}</td>
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button"
                                                            class="btn-icon"
                                                            title="Editar tarifa"
                                                            data-open-modal="editar-tarifa"
                                                            data-tarifa-valor="{{ (int) $tarifa->valorPago }}"
                                                            data-tarifa-nombre="{{ ($tarifa->rol->nombre ?? '') . ' · ' . ($tarifa->formato->nombre ?? '') }}"
                                                            data-tarifa-action="{{ route('tarifas.update', $tarifa->idTarifa) }}">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn-icon btn-icon-danger"
                                                            title="Eliminar tarifa"
                                                            data-delete-form="form-del-tarifa-{{ $tarifa->idTarifa }}"
                                                            data-confirm-title="¿Eliminar tarifa?"
                                                            data-confirm-text="Se eliminará la tarifa de {{ $tarifa->rol->nombre ?? '' }} ({{ $tarifa->formato->nombre ?? '' }}).">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                                <form id="form-del-tarifa-{{ $tarifa->idTarifa }}" method="POST"
                                                      action="{{ route('tarifas.destroy', $tarifa->idTarifa) }}" style="display:none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <form method="POST" action="{{ route('tarifas.store', $div->idDivision) }}" class="perfil-add-form">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Rol</label>
                                <select name="idRol" required class="form-select"
                                        data-nova-select data-placeholder="— Rol —">
                                    <option value="">— Rol —</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->idRol }}">{{ $rol->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Formato</label>
                                <select name="idFormato" required class="form-select"
                                        data-nova-select data-placeholder="— Formato —">
                                    <option value="">— Formato —</option>
                                    @foreach ($formatos as $fmt)
                                        <option value="{{ $fmt->idFormato }}">{{ $fmt->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor (COP)</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" name="valorPago" required
                                       placeholder="0" class="form-input">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-plus"></i>
                                Guardar tarifa
                            </button>
                        </form>
                    </div>
                @endforeach
            @endif

        </div>
    </div>

    {{-- ═
         SECCIÓN 4 — EMERGENTES
         ═ --}}
    <div class="perfil-step" id="step-emergentes">
        <div class="perfil-step-head">
            <h3>
                <span class="perfil-step-num">4</span>
                Árbitros Emergentes
                @if ($proximos->isNotEmpty())
                    <span class="perfil-step-count">{{ $proximos->flatten()->count() }} próximo{{ $proximos->flatten()->count() === 1 ? '' : 's' }}</span>
                @endif
            </h3>
            <i class="fa-solid fa-chevron-down perfil-step-toggle"></i>
        </div>
        <div class="perfil-step-body">

            {{-- 4a — Valor por disponibilidad --}}
            <div class="emergente-valor-wrap" style="margin-bottom:1.25rem;">
                @if ($torneo->valorEmergente === null)
                    <form method="POST" action="{{ route('torneos.perfil.guardar', $torneo->idTorneo) }}" id="form-valor-emergente">
                        @csrf
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;color:var(--t-text-2);font-size:0.9rem;">
                                <input type="checkbox" id="habilitar-emergentes" style="width:16px;height:16px;accent-color:var(--t-accent);">
                                ¿Este torneo maneja emergentes?
                            </label>
                        </div>
                        <div id="wrap-valor-emergente" style="display:none;margin-top:0.85rem;">
                            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                                <div class="form-group" style="margin:0;flex:0 0 200px;">
                                    <label class="form-label">Valor por disponibilidad (COP)</label>
                                    <input type="text" inputmode="numeric" pattern="[0-9]*"
                                           name="valorEmergente" id="input-valor-emergente"
                                           class="form-input" placeholder="0">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1.4rem;">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Guardar
                                </button>
                            </div>
                            <small class="field-hint">Pon 0 si los emergentes no reciben pago por disponibilidad.</small>
                        </div>
                    </form>
                @else
                    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <div>
                            <p style="margin:0;font-size:0.8rem;color:var(--t-text-2);">Valor por disponibilidad</p>
                            <p id="display-valor-emergente" style="margin:0;font-size:1.25rem;font-weight:700;color:var(--t-accent);">
                                $ {{ number_format((float) $torneo->valorEmergente, 0, ',', '.') }} COP
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-editar-valor">
                            <i class="fa-solid fa-pen"></i>
                            Editar valor
                        </button>
                    </div>
                    <form method="POST" action="{{ route('torneos.perfil.guardar', $torneo->idTorneo) }}"
                          id="form-editar-valor-emergente" style="display:none;margin-top:0.85rem;">
                        @csrf
                        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                            <div class="form-group" style="margin:0;flex:0 0 200px;">
                                <label class="form-label">Nuevo valor (COP)</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*"
                                       name="valorEmergente" class="form-input"
                                       value="{{ (int) $torneo->valorEmergente }}" placeholder="0">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1.4rem;">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Guardar
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-editar-valor" style="margin-top:1.4rem;">
                                Cancelar
                            </button>
                        </div>
                        <small class="field-hint">Deja en blanco para deshabilitar emergentes en este torneo.</small>
                    </form>
                @endif
            </div>

            {{-- 4b — Emergentes próximos / activos (hoy en adelante) --}}
            @php $hoy = \Carbon\Carbon::today(); @endphp
            <p style="font-size:0.8rem;font-weight:600;color:var(--t-text-2);text-transform:uppercase;letter-spacing:0.04em;margin:0 0 0.6rem;">
                <i class="fa-solid fa-user-clock" style="color:var(--t-accent);margin-right:0.3rem;"></i>
                Emergentes asignados
            </p>

            @if ($proximos->isEmpty())
                <div class="detail-empty" style="margin-bottom:1rem;">No hay emergentes asignados próximamente.</div>
            @else
                @foreach ($proximos as $fecha => $grupo)
                    @php $esFecha = \Carbon\Carbon::parse($fecha); @endphp
                    <div style="display:flex;align-items:center;gap:0.5rem;margin:1rem 0 0.4rem;">
                        @if ($esFecha->isToday())
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;background:rgba(34,197,94,.15);color:#22c55e;font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:99px;text-transform:uppercase;letter-spacing:0.05em;">
                                <i class="fa-solid fa-circle" style="font-size:0.45rem;"></i> Hoy
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;background:rgba(79,142,247,.12);color:#4f8ef7;font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:99px;">
                                <i class="fa-solid fa-calendar-day" style="font-size:0.65rem;"></i>
                                {{ $esFecha->translatedFormat('l d/m/Y') }}
                            </span>
                        @endif
                    </div>
                    <table class="tarifas-table" style="margin-bottom:0.5rem;">
                        <thead>
                            <tr>
                                <th>Árbitro</th>
                                <th>Sede</th>
                                <th>Notas</th>
                                <th>Asignado por</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grupo as $em)
                                <tr>
                                    <td>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</td>
                                    <td>{{ $em->sede->nombreSede ?? '—' }}</td>
                                    <td>{{ $em->notas ?? '—' }}</td>
                                    <td>{{ $em->asignador->nombreUsuario ?? '—' }}</td>
                                    <td>
                                        <button type="button"
                                                class="btn-icon btn-icon-danger"
                                                title="Eliminar emergente"
                                                data-delete-form="form-del-em-{{ $em->idEmergente }}"
                                                data-confirm-title="¿Eliminar emergente?"
                                                data-confirm-text="Se eliminará la asignación de {{ $em->arbitro->usuario->nombreUsuario ?? '' }} como emergente el {{ $esFecha->format('d/m/Y') }}.">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <form id="form-del-em-{{ $em->idEmergente }}" method="POST"
                                              action="{{ route('emergentes.destroy', [$torneo->idTorneo, $em->idEmergente]) }}" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endif

            {{-- Valor de pago resumen --}}
            @if ($torneo->valorEmergente !== null)
                <p class="field-hint" style="margin-top:0.5rem;margin-bottom:1rem;">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--t-accent);margin-right:0.3rem;"></i>
                    Valor por disponibilidad: <strong>$ {{ number_format((float) $torneo->valorEmergente, 0, ',', '.') }} COP</strong>
                </p>
            @endif

            {{-- 4b2 — Historial (fechas pasadas) --}}
            @if ($historial->isNotEmpty())
                <details class="reglamento-history" style="margin-top:1rem;">
                    <summary>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Historial de emergentes ({{ $historial->flatten()->count() }})
                    </summary>
                    @foreach ($historial as $fecha => $grupo)
                        @php $esFecha = \Carbon\Carbon::parse($fecha); @endphp
                        <div style="display:flex;align-items:center;gap:0.5rem;margin:0.9rem 0 0.4rem;">
                            <span style="display:inline-flex;align-items:center;gap:0.3rem;background:rgba(148,163,184,.10);color:#64748b;font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:99px;">
                                <i class="fa-solid fa-calendar-xmark" style="font-size:0.65rem;"></i>
                                {{ $esFecha->translatedFormat('l d/m/Y') }}
                            </span>
                        </div>
                        <table class="tarifas-table" style="margin-bottom:0.5rem;">
                            <thead>
                                <tr>
                                    <th>Árbitro</th>
                                    <th>Sede</th>
                                    <th>Notas</th>
                                    <th>Asignado por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grupo as $em)
                                    <tr>
                                        <td>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</td>
                                        <td>{{ $em->sede->nombreSede ?? '—' }}</td>
                                        <td>{{ $em->notas ?? '—' }}</td>
                                        <td>{{ $em->asignador->nombreUsuario ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </details>
            @endif

            {{-- 4c — Formulario agregar emergente --}}
            <div style="margin-top:1.25rem;">
                <p style="font-size:0.85rem;font-weight:600;color:var(--t-text-2);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.04em;">
                    <i class="fa-solid fa-user-plus" style="color:var(--t-accent);margin-right:0.3rem;"></i>
                    Asignar emergente
                </p>
                <form method="POST" action="{{ route('emergentes.store', $torneo->idTorneo) }}" class="perfil-add-form">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Árbitro activo <span class="req">*</span></label>
                        <select name="idArbitro" required class="form-select"
                                data-nova-select data-searchable="true" data-placeholder="Selecciona árbitro">
                            <option value="">Selecciona árbitro</option>
                            @foreach ($arbitros as $arb)
                                <option value="{{ $arb->idArbitro }}">{{ $arb->usuario->nombreUsuario ?? '—' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sede <span class="req">*</span></label>
                        <select name="idSede" required class="form-select"
                                data-nova-select data-placeholder="Selecciona sede">
                            <option value="">Selecciona sede</option>
                            @foreach ($torneo->sedes as $sede)
                                <option value="{{ $sede->idSede }}">{{ $sede->nombreSede }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha <span class="req">*</span></label>
                        <input type="text" name="fechaEmergente" required class="form-input"
                               data-nova-date placeholder="dd/mm/aaaa">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">
                            Notas
                            <span id="contador-notas" style="font-size:0.78rem;color:#8892a4;margin-left:0.4rem;">0/300</span>
                        </label>
                        <textarea id="notas-emergente" name="notas" maxlength="300" rows="2"
                                  class="form-textarea" placeholder="Observaciones opcionales"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus"></i>
                        Asignar emergente
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- ═
         SECCIÓN 5 — REGLAMENTO (versionado)
         ═ --}}
    @php $reglamentoActual = $torneo->reglamentoActual; @endphp

    <div class="perfil-step" id="step-reglamento">
        <div class="perfil-step-head">
            <h3>
                <span class="perfil-step-num">5</span>
                Reglamento (PDF)
                @if ($torneo->reglamentos->isNotEmpty())
                    <span class="perfil-step-count">{{ $torneo->reglamentos->count() }} versión{{ $torneo->reglamentos->count() === 1 ? '' : 'es' }} anterior{{ $torneo->reglamentos->count() === 1 ? '' : 'es' }}</span>
                @endif
            </h3>
            <i class="fa-solid fa-chevron-down perfil-step-toggle"></i>
        </div>
        <div class="perfil-step-body">

            @if ($reglamentoActual)
                <div class="reglamento-card">
                    <i class="fa-solid fa-file-pdf reg-icon"></i>
                    <div class="reglamento-card-info">
                        <strong>{{ $reglamentoActual->nombreArchivo }}</strong>
                        <small>
                            {{ $reglamentoActual->tamano_legible }}
                            · subido el {{ $reglamentoActual->created_at?->format('d/m/Y H:i') }}
                            · por {{ $reglamentoActual->subidoPor->nombreUsuario ?? 'sistema' }}
                        </small>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm"
                            data-open-modal="ver-reglamento"
                            data-pdf-url="{{ asset('storage/' . $reglamentoActual->rutaArchivo) }}"
                            data-pdf-name="{{ $reglamentoActual->nombreArchivo }}">
                        <i class="fa-solid fa-eye"></i>
                        Ver PDF
                    </button>
                    <button type="button"
                            class="btn-icon btn-icon-danger"
                            title="Eliminar reglamento"
                            data-delete-form="form-del-reg-{{ $reglamentoActual->idReglamento }}"
                            data-confirm-title="¿Eliminar reglamento?"
                            data-confirm-text="Se eliminará «{{ $reglamentoActual->nombreArchivo }}» del servidor. Esta acción no se puede deshacer.">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <form id="form-del-reg-{{ $reglamentoActual->idReglamento }}" method="POST"
                          action="{{ route('reglamentos.destroy', $reglamentoActual->idReglamento) }}" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            @else
                <div class="detail-empty" style="margin-bottom:1rem;">
                    Aún no has subido el reglamento del torneo.
                </div>
            @endif

            <form method="POST" action="{{ route('torneos.perfil.guardar', $torneo->idTorneo) }}"
                  enctype="multipart/form-data" style="margin-top:1rem;">
                @csrf
                <label class="btn btn-secondary" for="input-reglamento" style="cursor:pointer;">
                    <i class="fa-solid fa-upload"></i>
                    {{ $reglamentoActual ? 'Subir nuevo reglamento' : 'Subir PDF' }}
                </label>
                <input type="file" id="input-reglamento" name="reglamentoPDF" accept=".pdf" style="display:none;">
                <span class="field-hint" style="margin-left:0.6rem;">
                    Máximo 60 MB · solo PDF{{ $reglamentoActual ? ' · el anterior queda en el historial' : '' }}
                </span>
            </form>

            {{-- Historial de versiones anteriores --}}
            @if ($torneo->reglamentos->isNotEmpty())
                <details class="reglamento-history" style="margin-top:1.5rem;">
                    <summary>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Historial de reglamentos anteriores ({{ $torneo->reglamentos->count() }})
                    </summary>
                    <table class="tarifas-table" style="margin-top:0.85rem;">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha</th>
                                <th>Tamaño</th>
                                <th>Subido por</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($torneo->reglamentos as $hist)
                                <tr>
                                    <td>
                                        <i class="fa-solid fa-file-pdf" style="color:var(--t-text-mute);margin-right:0.3rem;"></i>
                                        {{ $hist->nombreArchivo }}
                                    </td>
                                    <td>{{ $hist->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $hist->tamano_legible }}</td>
                                    <td>{{ $hist->subidoPor->nombreUsuario ?? '—' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn-icon"
                                                    title="Ver versión"
                                                    data-open-modal="ver-reglamento"
                                                    data-pdf-url="{{ asset('storage/' . $hist->rutaArchivo) }}"
                                                    data-pdf-name="{{ $hist->nombreArchivo }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn-icon btn-icon-danger"
                                                    title="Eliminar versión"
                                                    data-delete-form="form-del-reg-{{ $hist->idReglamento }}"
                                                    data-confirm-title="¿Eliminar esta versión?"
                                                    data-confirm-text="Se eliminará «{{ $hist->nombreArchivo }}» del servidor. Esta acción no se puede deshacer.">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                        <form id="form-del-reg-{{ $hist->idReglamento }}" method="POST"
                                              action="{{ route('reglamentos.destroy', $hist->idReglamento) }}" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            @endif

        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
        <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-primary">
            Ir al torneo
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

</div>

{{-- ═══════════ MODAL EDITAR DIVISIÓN ═══════════ --}}
<div class="modal" id="modal-editar-division" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" id="form-editar-division">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Editar división</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre <span class="req">*</span></label>
                    <input type="text" id="edit-div-nombre" name="nombreDivision" maxlength="100" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" id="edit-div-descripcion" name="descripcion" class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════ MODAL EDITAR SEDE ═══════════ --}}
<div class="modal" id="modal-editar-sede" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" id="form-editar-sede">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Editar sede</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre <span class="req">*</span></label>
                        <input type="text" id="edit-sede-nombre" name="nombreSede" maxlength="150" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Municipio <span class="req">*</span></label>
                        <input type="text" id="edit-sede-municipio" name="municipio" maxlength="100" required class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Dirección <span class="req">*</span></label>
                        <input type="text" id="edit-sede-direccion" name="direccion" maxlength="255" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Departamento</label>
                        <input type="text" id="edit-sede-departamento" name="departamento" maxlength="100" placeholder="Opcional" class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">URL de Google Maps</label>
                        <input type="url" id="edit-sede-urlmaps" name="urlMaps" maxlength="500" placeholder="https://maps.google.com/..." class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Observaciones</label>
                        <textarea id="edit-sede-observaciones" name="observaciones" rows="2" class="form-textarea"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════ MODAL EDITAR TARIFA ═══════════ --}}
<div class="modal" id="modal-editar-tarifa" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog" style="max-width:400px;">
        <form method="POST" id="form-editar-tarifa">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Editar tarifa</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="edit-tarifa-nombre" style="margin:0 0 1rem;color:var(--t-text);font-size:0.9rem;"></p>
                <div class="form-group">
                    <label class="form-label">Valor (COP) <span class="req">*</span></label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" id="edit-tarifa-valor"
                           name="valorPago" required class="form-input" placeholder="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════ MODAL VER REGLAMENTO (iframe) ═══════════ --}}
<div class="modal" id="modal-ver-reglamento" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog modal-dialog--xl">
        <div class="modal-header">
            <h3 class="modal-title" id="pdf-name-label">
                <i class="fa-solid fa-file-pdf" style="color:#ef4444;margin-right:0.5rem;"></i>
                Reglamento
            </h3>
            <a id="pdf-open-tab" href="#" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="margin-left:auto;margin-right:0.6rem;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Abrir en pestaña
            </a>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body" style="padding:0;">
            <iframe id="pdf-frame" src="about:blank" style="width:100%;height:75vh;border:none;background:#0f1117;"></iframe>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
    <script>
        // Poblar modal editar división
        document.querySelectorAll('[data-open-modal="editar-division"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('form-editar-division').action = btn.dataset.divAction;
                document.getElementById('edit-div-nombre').value       = btn.dataset.divNombre;
                document.getElementById('edit-div-descripcion').value  = btn.dataset.divDescripcion;
            });
        });

        // Poblar modal editar sede
        document.querySelectorAll('[data-open-modal="editar-sede"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('form-editar-sede').action        = btn.dataset.sedeAction;
                document.getElementById('edit-sede-nombre').value         = btn.dataset.sedeNombre;
                document.getElementById('edit-sede-direccion').value      = btn.dataset.sedeDireccion;
                document.getElementById('edit-sede-municipio').value      = btn.dataset.sedeMunicipio;
                document.getElementById('edit-sede-departamento').value   = btn.dataset.sedeDepartamento;
                document.getElementById('edit-sede-urlmaps').value        = btn.dataset.sedeUrlmaps;
                document.getElementById('edit-sede-observaciones').value  = btn.dataset.sedeObservaciones;
            });
        });

        // Poblar modal editar tarifa
        document.querySelectorAll('[data-open-modal="editar-tarifa"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('form-editar-tarifa').action  = btn.dataset.tarifaAction;
                document.getElementById('edit-tarifa-valor').value    = btn.dataset.tarifaValor;
                document.getElementById('edit-tarifa-nombre').textContent = btn.dataset.tarifaNombre;
            });
        });

        // Inyectar URL del PDF cuando se abre el modal de visualización
        document.querySelectorAll('[data-open-modal="ver-reglamento"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url   = btn.dataset.pdfUrl;
                var name  = btn.dataset.pdfName || 'Reglamento';
                var frame = document.getElementById('pdf-frame');
                var label = document.getElementById('pdf-name-label');
                var link  = document.getElementById('pdf-open-tab');
                if (frame && url) frame.src = url;
                if (label) label.innerHTML = '<i class="fa-solid fa-file-pdf" style="color:#ef4444;margin-right:0.5rem;"></i>' + name;
                if (link)  link.href = url;
            });
        });

        // Vaciar iframe al cerrar para liberar recursos
        var modalPdf = document.getElementById('modal-ver-reglamento');
        if (modalPdf) {
            modalPdf.addEventListener('click', function (e) {
                if (e.target.closest('[data-close-modal]') || e.target === modalPdf) {
                    setTimeout(function () {
                        var frame = document.getElementById('pdf-frame');
                        if (frame) frame.src = 'about:blank';
                    }, 200);
                }
            });
        }

        // Toggle habilitar emergentes (checkbox)
        var chkEmergente = document.getElementById('habilitar-emergentes');
        var wrapValor    = document.getElementById('wrap-valor-emergente');
        if (chkEmergente && wrapValor) {
            chkEmergente.addEventListener('change', function () {
                wrapValor.style.display = chkEmergente.checked ? '' : 'none';
            });
        }

        // Editar valor emergente (cuando ya existe)
        var btnEditarValor = document.getElementById('btn-editar-valor');
        var formEditarValor = document.getElementById('form-editar-valor-emergente');
        var displayValor    = document.getElementById('display-valor-emergente');
        if (btnEditarValor && formEditarValor) {
            btnEditarValor.addEventListener('click', function () {
                formEditarValor.style.display = '';
                btnEditarValor.style.display  = 'none';
                if (displayValor) displayValor.style.display = 'none';
            });
            var btnCancelarEditar = document.getElementById('btn-cancelar-editar-valor');
            if (btnCancelarEditar) {
                btnCancelarEditar.addEventListener('click', function () {
                    formEditarValor.style.display = 'none';
                    btnEditarValor.style.display  = '';
                    if (displayValor) displayValor.style.display = '';
                });
            }
        }

        // Contador notas emergente
        var notasEm = document.getElementById('notas-emergente');
        var contEm  = document.getElementById('contador-notas');
        if (notasEm && contEm) {
            notasEm.addEventListener('input', function () {
                var len = notasEm.value.length;
                contEm.textContent = len + '/300';
                contEm.style.color = len >= 270 ? '#ef4444' : '#8892a4';
            });
        }
    </script>
@endpush
