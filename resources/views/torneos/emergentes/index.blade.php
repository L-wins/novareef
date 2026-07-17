@extends('layouts.app')

@section('titulo', 'Emergentes — ' . $torneo->nombreTorneo)
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('torneos.perfil', $torneo->idTorneo) }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver al perfil del torneo
    </a>

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-heading">Árbitros Emergentes</h1>
            <p class="page-subheading">{{ $torneo->nombreTorneo }} · Temporada {{ $torneo->temporada }}</p>
        </div>
        <a href="{{ route('torneos.show', $torneo->idTorneo) }}" class="btn btn-secondary">
            <i class="fa-solid fa-eye"></i>
            Ver torneo
        </a>
    </div>

    @if ($errors->any())
        <div class="form-note form-note--warn" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                @foreach ($errors->all() as $error) <p style="margin:0;">{{ $error }}</p> @endforeach
            </div>
        </div>
    @endif

    {{-- Valor por disponibilidad --}}
    @if ($torneo->valorEmergente !== null)
    <div class="detail-grid-card" style="margin-bottom:1.25rem;">
        <p class="detail-grid-card-title"><i class="fa-solid fa-money-bill-wave"></i> Valor por disponibilidad</p>
        <p style="font-size:1.4rem;font-weight:700;color:var(--t-accent);margin:0;">
            $ {{ number_format((float) $torneo->valorEmergente, 0, ',', '.') }} COP
        </p>
    </div>
    @endif

    {{-- Emergentes asignados (próximos + hoy) --}}
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-user-clock"></i> Emergentes asignados</p>

        @if ($proximos->isEmpty())
            <div class="detail-empty">No hay emergentes asignados próximamente.</div>
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
                <div class="perfil-list" style="margin-bottom:0.25rem;">
                    @foreach ($grupo as $em)
                        <div class="perfil-list-item">
                            <div class="perfil-list-info">
                                <strong>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</strong>
                                <small>
                                    <i class="fa-solid fa-location-dot" style="margin-right:0.2rem;"></i>
                                    {{ $em->sede->nombreSede ?? '—' }}
                                    @if ($em->notas) · {{ $em->notas }} @endif
                                </small>
                                <small style="color:var(--t-text-muted);">
                                    Asignado por {{ $em->asignador->nombreUsuario ?? '—' }}
                                </small>
                            </div>
                            <div class="table-actions">
                                <button type="button"
                                        class="btn-icon btn-icon-danger"
                                        title="Eliminar emergente"
                                        data-delete-form="form-del-em-{{ $em->idEmergente }}"
                                        data-confirm-title="¿Eliminar emergente?"
                                        data-confirm-text="Se eliminará la asignación de {{ $em->arbitro->usuario->nombreUsuario ?? '' }} como emergente el {{ $esFecha->format('d/m/Y') }}.">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <form id="form-del-em-{{ $em->idEmergente }}" method="POST"
                                  action="{{ route('torneos.emergentes.destroy', [$torneo->idTorneo, $em->idEmergente]) }}" style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    {{-- Historial (fechas pasadas) --}}
    @if ($historial->isNotEmpty())
    <div class="detail-grid-card">
        <details class="reglamento-history" open style="border:none;background:transparent;">
            <summary style="padding:0;margin-bottom:0.75rem;">
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
                <div class="perfil-list" style="margin-bottom:0.25rem;">
                    @foreach ($grupo as $em)
                        <div class="perfil-list-item" style="cursor:default;">
                            <div class="perfil-list-info">
                                <strong>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</strong>
                                <small>
                                    <i class="fa-solid fa-location-dot" style="margin-right:0.2rem;"></i>
                                    {{ $em->sede->nombreSede ?? '—' }}
                                    @if ($em->notas) · {{ $em->notas }} @endif
                                </small>
                                <small style="color:var(--t-text-muted);">
                                    Asignado por {{ $em->asignador->nombreUsuario ?? '—' }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </details>
    </div>
    @endif

    {{-- Formulario agregar emergente --}}
    @can('crear-designaciones')
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-user-plus"></i> Asignar emergente</p>

        <form method="POST" action="{{ route('torneos.emergentes.store', $torneo->idTorneo) }}" class="perfil-add-form">
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
                    <span id="contador-notas-em" style="font-size:0.78rem;color:#8892a4;margin-left:0.4rem;">0/300</span>
                </label>
                <textarea id="notas-emergente-em" name="notas" maxlength="300" rows="2"
                          class="form-textarea" placeholder="Observaciones opcionales"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i>
                Asignar emergente
            </button>
        </form>
    </div>
    @endcan

</div>
@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
    <script>
        var notasEm = document.getElementById('notas-emergente-em');
        var contEm  = document.getElementById('contador-notas-em');
        if (notasEm && contEm) {
            notasEm.addEventListener('input', function () {
                var len = notasEm.value.length;
                contEm.textContent = len + '/300';
                contEm.style.color = len >= 270 ? '#ef4444' : '#8892a4';
            });
        }
    </script>
@endpush
