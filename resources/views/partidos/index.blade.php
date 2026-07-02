@extends('layouts.app')

@section('titulo', 'Partidos — ' . $torneo->nombreTorneo)
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a {{ $torneo->nombreTorneo }}
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Partidos</h1>
            <p class="page-subheading">
                {{ $partidos->total() }} partido{{ $partidos->total() === 1 ? '' : 's' }} ·
                {{ $torneo->nombreTorneo }}
            </p>
        </div>
        @can('crear-torneos')
            <button type="button" class="btn btn-primary" data-open-modal="nuevo-partido">
                <i class="fa-solid fa-plus"></i>
                Nuevo partido
            </button>
        @endcan
    </div>

    @if ($errors->any())
        <div class="form-note form-note--warn" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                @foreach ($errors->all() as $error) <p style="margin:0;">{{ $error }}</p> @endforeach
            </div>
        </div>
    @endif

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('partidos.index', $torneo->idTorneo) }}" class="filter-bar-grid">
        <div class="filter-group">
            <label class="filter-label">Estado</label>
            <select name="estado" class="filter-select"
                    data-nova-select data-placeholder="Estado">
                <option value="">Todos</option>
                @foreach (['programado', 'en_curso', 'finalizado', 'aplazado', 'cancelado'] as $val)
                    <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', ucfirst($val)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">División</label>
            <select name="division" class="filter-select"
                    data-nova-select data-placeholder="División">
                <option value="">Todas</option>
                @foreach ($torneo->divisiones as $div)
                    <option value="{{ $div->idDivision }}" {{ (string) request('division') === (string) $div->idDivision ? 'selected' : '' }}>
                        {{ $div->nombreDivision }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Fecha</label>
            <input type="text" name="fecha" data-nova-date placeholder="dd/mm/aaaa"
                   value="{{ request('fecha') }}" class="filter-input">
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>
            @if (request()->hasAny(['estado', 'division', 'fecha']))
                <a href="{{ route('partidos.index', $torneo->idTorneo) }}" class="filter-clear">Limpiar</a>
            @endif
        </div>
    </form>

    @if ($partidos->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-futbol" style="font-size:48px;margin-bottom:1rem;opacity:.45;"></i>
            @if (request()->hasAny(['estado', 'division', 'fecha']))
                <p>No hay partidos que coincidan con los filtros.</p>
            @else
                <p>Aún no hay partidos registrados en este torneo.</p>
                @can('crear-torneos')
                    <button type="button" class="btn btn-primary" data-open-modal="nuevo-partido" style="margin-top:1rem;">
                        <i class="fa-solid fa-plus"></i>
                        Registrar primer partido
                    </button>
                @endcan
            @endif
        </div>
    @else
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>División</th>
                        <th>Equipos</th>
                        <th>Sede</th>
                        <th>Formato</th>
                        <th>Estado</th>
                        @can('editar-torneos')
                            <th>Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($partidos as $p)
                    <tr>
                        <td>
                            <span class="td-primary">{{ $p->fechaPartido->format('d/m/Y') }}</span>
                            <span class="td-secondary">{{ \Illuminate\Support\Carbon::parse($p->horaPartido)->format('H:i') }}</span>
                        </td>
                        <td>{{ $p->division->nombreDivision ?? '—' }}</td>
                        <td>
                            <div class="equipos">
                                <span>{{ $p->equipoLocal }}</span>
                                <span class="vs">vs</span>
                                <span>{{ $p->equipoVisitante }}</span>
                            </div>
                        </td>
                        <td>{{ $p->sede->nombreSede ?? '—' }}</td>
                        <td>{{ $p->formato->nombre ?? '—' }}</td>
                        <td>
                            <span class="t-badge" data-estado-p="{{ $p->estadoPartido }}">
                                {{ str_replace('_', ' ', ucfirst($p->estadoPartido)) }}
                            </span>
                        </td>
                        @can('editar-torneos')
                            <td>
                                <div class="table-actions">
                                    <button type="button"
                                            class="btn-icon"
                                            title="Cambiar estado"
                                            data-open-modal="estado-partido-{{ $p->idPartido }}">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </button>
                                </div>
                            </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($partidos->hasPages())
            <div class="pagination-wrapper">{{ $partidos->links() }}</div>
        @endif
    @endif

</div>

{{-- ═══════════════ MODAL NUEVO PARTIDO ═══════════════ --}}
@can('crear-torneos')
<div class="modal" id="modal-nuevo-partido" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog" style="max-width:680px;">
        <form method="POST" action="{{ route('partidos.store', $torneo->idTorneo) }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Nuevo partido</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">División <span class="req">*</span></label>
                        <select name="idDivision" required class="form-select"
                                data-nova-select data-placeholder="Selecciona división">
                            <option value="">— Selecciona —</option>
                            @foreach ($torneo->divisiones as $div)
                                <option value="{{ $div->idDivision }}">{{ $div->nombreDivision }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sede</label>
                        <select name="idSede" class="form-select"
                                data-nova-select data-placeholder="Sin sede">
                            <option value="">— Sin sede —</option>
                            @foreach ($torneo->sedes as $sede)
                                <option value="{{ $sede->idSede }}">{{ $sede->nombreSede }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Formato <span class="req">*</span></label>
                        <select name="idFormato" required class="form-select"
                                data-nova-select data-placeholder="Selecciona formato">
                            <option value="">— Selecciona —</option>
                            @foreach ($formatos as $fmt)
                                <option value="{{ $fmt->idFormato }}">{{ $fmt->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Modalidad de pago</label>
                        <input type="text" disabled
                               value="{{ $torneo->modalidadPago === 'campo' ? 'Pago en campo' : 'Por nómina' }}"
                               class="form-input">
                        <small class="field-hint">Hereda del torneo.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Equipo local <span class="req">*</span></label>
                        <input type="text" name="equipoLocal" maxlength="150" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Equipo visitante <span class="req">*</span></label>
                        <input type="text" name="equipoVisitante" maxlength="150" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha <span class="req">*</span></label>
                        <input type="text" name="fechaPartido" required
                               data-nova-date placeholder="dd/mm/aaaa"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora <span class="req">*</span></label>
                        <input type="time" name="horaPartido" required class="form-input">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" rows="2" class="form-textarea"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    Crear partido
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ═══════════════ MODALES POR PARTIDO: CAMBIO DE ESTADO + RESULTADO ═══════════════ --}}
@can('editar-torneos')
@foreach ($partidos as $p)
<div class="modal" id="modal-estado-partido-{{ $p->idPartido }}" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog">
        <form method="POST" action="{{ route('partidos.estado', ['torneoId' => $torneo->idTorneo, 'id' => $p->idPartido]) }}"
              data-confirm-submit
              data-confirm-title="¿Actualizar el partido?"
              data-confirm-text="Se guardará el nuevo estado.">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">
                    {{ $p->equipoLocal }} <span style="color:var(--t-text-mute);">vs</span> {{ $p->equipoVisitante }}
                </h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nuevo estado <span class="req">*</span></label>
                    <select name="estadoNuevo" required class="form-select"
                            data-partido="{{ $p->idPartido }}">
                        @foreach (['programado', 'en_curso', 'finalizado', 'aplazado', 'cancelado'] as $val)
                            <option value="{{ $val }}" {{ $p->estadoPartido === $val ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucfirst($val)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endcan

@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
@endpush
