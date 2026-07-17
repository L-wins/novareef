@extends('layouts.app')

@section('titulo', $torneo->nombreTorneo)
@section('seccion', 'Torneos')

@push('styles')
    @vite(['resources/css/torneos/torneos.css'])
@endpush

@section('contenido')
<div class="container">

    <a href="{{ route('torneos.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Volver a torneos
    </a>

    @if ($errors->any())
        <div class="form-note form-note--warn" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                @foreach ($errors->all() as $error) <p style="margin:0;">{{ $error }}</p> @endforeach
            </div>
        </div>
    @endif

    {{-- HERO --}}
    <div class="torneo-hero">
        <div class="torneo-hero-info">
            <h1 class="torneo-hero-name">{{ $torneo->nombreTorneo }}</h1>
            <div class="torneo-hero-meta">
                <span class="t-badge" data-tipo="{{ $torneo->tipoTorneo }}">{{ ucfirst($torneo->tipoTorneo) }}</span>
                <span class="t-badge" data-estado="{{ $torneo->estadoTorneo }}">{{ ucfirst($torneo->estadoTorneo) }}</span>
                <span class="t-badge" data-tipo="local" style="background:rgba(148,163,184,.10);">Temporada {{ $torneo->temporada }}</span>
            </div>
            <div class="torneo-hero-meta--text">
                <span><i class="fa-solid fa-calendar-day"></i>{{ $torneo->fechaInicio->format('d/m/Y') }} – {{ $torneo->fechaFin->format('d/m/Y') }}</span>
                <span><i class="fa-solid fa-user-tie"></i>{{ $torneo->organizadorNombre }}</span>
                <span><i class="fa-solid fa-money-bill-wave"></i>{{ $torneo->modalidadPago === 'campo' ? 'Pago en campo' : 'Por nómina' }}</span>
            </div>
        </div>

        <div class="torneo-hero-actions">
            <a href="{{ route('partidos.index', $torneo->idTorneo) }}" class="btn btn-primary">
                <i class="fa-solid fa-futbol"></i>
                Partidos
            </a>
            @can('editar-torneos')
                <a href="{{ route('torneos.perfil', $torneo->idTorneo) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-sliders"></i>
                    Perfil
                </a>
                <a href="{{ route('torneos.edit', $torneo->idTorneo) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar
                </a>
                <button type="button" class="btn btn-warning" data-open-modal="cambio-estado-torneo">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Cambiar estado
                </button>
                <form method="POST" action="{{ route('torneos.archivar', $torneo->idTorneo) }}"
                      style="display:contents;"
                      data-confirm-submit
                      data-confirm-title="Archivar torneo"
                      data-confirm-text="¿Archivar «{{ $torneo->nombreTorneo }}»? Dejará de aparecer en el listado. Esta acción no se puede deshacer desde la interfaz."
                      data-confirm-btn="Sí, archivar">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-box-archive"></i>
                        Archivar
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @if ($resumenCobro)
        @php
            $pendienteDeRegistrar = max(0, $resumenCobro['nomina']['totalEgresos'] - $resumenCobro['ingresos']['totalIngresos']);
        @endphp
        <div class="detail-grid-card">
            <p class="detail-grid-card-title"><i class="fa-solid fa-hand-holding-dollar"></i> Estado de cobro al organizador</p>
            <div class="detail-pairs">
                <div class="detail-pair">
                    <span class="detail-pair-label">Nómina generada</span>
                    <span class="detail-pair-value">${{ number_format($resumenCobro['nomina']['totalEgresos'], 0, ',', '.') }}</span>
                </div>
                <div class="detail-pair">
                    <span class="detail-pair-label">Pendiente de registrar</span>
                    <span class="detail-pair-value">${{ number_format($pendienteDeRegistrar, 0, ',', '.') }}</span>
                </div>
                <div class="detail-pair">
                    <span class="detail-pair-label">Ya registrado como cobrado</span>
                    <span class="detail-pair-value">${{ number_format($resumenCobro['ingresos']['totalIngresos'], 0, ',', '.') }}</span>
                </div>
            </div>
            @can('crear-finanzas')
                @if ($pendienteDeRegistrar > 0)
                    <a href="{{ route('finanzas.institucional.index', ['abrir' => 'registrar', 'categoria' => 'ingreso_torneo', 'idTorneo' => $torneo->idTorneo]) }}"
                       class="btn btn-secondary btn-sm" style="margin-top:.75rem">
                        <i class="fa-solid fa-plus"></i> Registrar ingreso de torneo
                    </a>
                @endif
            @endcan
        </div>
    @endif

    {{-- 1. INFORMACIÓN GENERAL --}}
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-circle-info"></i> Información general</p>
        <div class="detail-pairs">
            <div class="detail-pair">
                <span class="detail-pair-label">Tipo</span>
                <span class="detail-pair-value">{{ ucfirst($torneo->tipoTorneo) }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Modalidad de pago</span>
                <span class="detail-pair-value">{{ $torneo->modalidadPago === 'campo' ? 'Pago en campo' : 'Por nómina' }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Temporada</span>
                <span class="detail-pair-value">{{ $torneo->temporada }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Estado</span>
                <span class="detail-pair-value">{{ ucfirst($torneo->estadoTorneo) }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Fecha de inicio</span>
                <span class="detail-pair-value">{{ $torneo->fechaInicio->format('d/m/Y') }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Fecha de fin</span>
                <span class="detail-pair-value">{{ $torneo->fechaFin->format('d/m/Y') }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Organizador</span>
                <span class="detail-pair-value">{{ $torneo->organizadorNombre }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Teléfono</span>
                <span class="detail-pair-value">{{ $torneo->organizadorTelefono ?? '—' }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Email</span>
                <span class="detail-pair-value">{{ $torneo->organizadorEmail ?? '—' }}</span>
            </div>
            <div class="detail-pair">
                <span class="detail-pair-label">Creado por</span>
                <span class="detail-pair-value">{{ $torneo->creador->nombreUsuario ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- 2. DIVISIONES --}}
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-layer-group"></i> Divisiones y tarifas</p>
        @if ($torneo->divisiones->isEmpty())
            <div class="detail-empty">No hay divisiones registradas.</div>
        @else
            @foreach ($torneo->divisiones as $div)
                <div class="tarifas-block">
                    <h4 class="tarifas-block-title">
                        <i class="fa-solid fa-layer-group" style="color:var(--t-accent);"></i>
                        {{ $div->nombreDivision }}
                    </h4>
                    @if ($div->tarifas->isEmpty())
                        <div class="detail-empty">Sin tarifas configuradas.</div>
                    @else
                        <table class="tarifas-table">
                            <thead>
                                <tr>
                                    <th>Rol</th>
                                    <th>Formato</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($div->tarifas as $tarifa)
                                    <tr>
                                        <td>{{ $tarifa->rol->nombre ?? '—' }}</td>
                                        <td>{{ $tarifa->formato->nombre ?? '—' }}</td>
                                        <td class="tarifa-monto">$ {{ number_format((float) $tarifa->valorPago, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    {{-- 3. SEDES --}}
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-location-dot"></i> Sedes</p>
        @if ($torneo->sedes->isEmpty())
            <div class="detail-empty">No hay sedes registradas.</div>
        @else
            <div class="perfil-list">
                @foreach ($torneo->sedes as $sede)
                    <div class="perfil-list-item" style="cursor:default;">
                        <div class="perfil-list-info">
                            <strong>{{ $sede->nombreSede }}</strong>
                            <small>{{ $sede->direccion }} · {{ $sede->municipio }}</small>
                            @if ($sede->observaciones)
                                <small>{{ $sede->observaciones }}</small>
                            @endif
                        </div>
                        @if ($sede->urlMaps)
                            <a href="{{ $sede->urlMaps }}" target="_blank" rel="noopener"
                               class="btn btn-secondary btn-sm" title="Abrir en Google Maps">
                                <i class="fa-solid fa-map-location-dot"></i>
                                Ver en Maps
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 4. PARTIDOS --}}
    <div class="detail-grid-card">
        <p class="detail-grid-card-title">
            <i class="fa-solid fa-futbol"></i>
            Partidos
            <a href="{{ route('partidos.index', $torneo->idTorneo) }}" style="margin-left:auto;font-size:0.78rem;color:var(--t-accent);text-decoration:none;">
                Ver todos →
            </a>
        </p>
        @if ($torneo->partidos->isEmpty())
            <div class="detail-empty">No hay partidos registrados.</div>
        @else
            <div class="table-card" style="margin-bottom:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Equipos</th>
                            <th>División</th>
                            <th>Sede</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($torneo->partidos->take(10) as $p)
                            <tr>
                                <td>
                                    <span class="td-primary">{{ $p->fechaPartido->format('d/m/Y') }}</span>
                                    <span class="td-secondary">{{ \Illuminate\Support\Carbon::parse($p->horaPartido)->format('H:i') }}</span>
                                </td>
                                <td>
                                    <div class="equipos">
                                        <span>{{ $p->equipoLocal }}</span>
                                        <span class="vs">vs</span>
                                        <span>{{ $p->equipoVisitante }}</span>
                                    </div>
                                </td>
                                <td>{{ $p->division->nombreDivision ?? '—' }}</td>
                                <td>{{ $p->sede->nombreSede ?? '—' }}</td>
                                <td><span class="t-badge" data-estado-p="{{ $p->estadoPartido }}">{{ str_replace('_', ' ', ucfirst($p->estadoPartido)) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- 5. EMERGENTES --}}
    @if ($torneo->valorEmergente !== null || $torneo->emergentes->isNotEmpty())
    @php
        $hoyShow    = \Carbon\Carbon::today();
        $proximosShow = $torneo->emergentes->filter(fn ($e) => !$e->fechaEmergente->lt($hoyShow))->sortBy('fechaEmergente')->groupBy(fn ($e) => $e->fechaEmergente->format('Y-m-d'));
        $historialShow = $torneo->emergentes->filter(fn ($e) => $e->fechaEmergente->lt($hoyShow))->sortByDesc('fechaEmergente')->groupBy(fn ($e) => $e->fechaEmergente->format('Y-m-d'));
    @endphp
    <div class="detail-grid-card">
        <p class="detail-grid-card-title">
            <i class="fa-solid fa-user-clock"></i> Árbitros Emergentes
            <span style="margin-left:auto;display:flex;align-items:center;gap:0.75rem;">
                @if ($torneo->valorEmergente !== null)
                    <span style="font-size:0.78rem;color:var(--t-text-2);">
                        Disponibilidad:
                        <strong style="color:var(--t-accent);">$ {{ number_format((float) $torneo->valorEmergente, 0, ',', '.') }} COP</strong>
                    </span>
                @endif
                @can('crear-designaciones')
                    <a href="{{ route('torneos.emergentes.index', $torneo->idTorneo) }}" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-user-gear"></i> Gestionar
                    </a>
                @endcan
            </span>
        </p>

        {{-- Próximos --}}
        @if ($proximosShow->isEmpty() && $historialShow->isEmpty())
            <div class="detail-empty">No hay emergentes registrados.</div>
        @endif

        @if ($proximosShow->isNotEmpty())
            @foreach ($proximosShow as $fecha => $grupo)
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
                        <div class="perfil-list-item" style="cursor:default;">
                            <div class="perfil-list-info">
                                <strong>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</strong>
                                <small>
                                    <i class="fa-solid fa-location-dot" style="margin-right:0.2rem;"></i>
                                    {{ $em->sede->nombreSede ?? '—' }}
                                    @if ($em->notas) · {{ $em->notas }} @endif
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif

        {{-- Historial colapsado --}}
        @if ($historialShow->isNotEmpty())
            <details class="reglamento-history" style="margin-top:0.85rem;">
                <summary>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Historial de emergentes ({{ $historialShow->flatten()->count() }})
                </summary>
                @foreach ($historialShow as $fecha => $grupo)
                    @php $esFecha = \Carbon\Carbon::parse($fecha); @endphp
                    <div style="display:flex;align-items:center;gap:0.5rem;margin:0.85rem 0 0.3rem;">
                        <span style="display:inline-flex;align-items:center;gap:0.3rem;background:rgba(148,163,184,.10);color:#64748b;font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:99px;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size:0.65rem;"></i>
                            {{ $esFecha->translatedFormat('l d/m/Y') }}
                        </span>
                    </div>
                    <div class="perfil-list" style="margin-bottom:0.2rem;">
                        @foreach ($grupo as $em)
                            <div class="perfil-list-item" style="cursor:default;">
                                <div class="perfil-list-info">
                                    <strong>{{ $em->arbitro->usuario->nombreUsuario ?? '—' }}</strong>
                                    <small>
                                        <i class="fa-solid fa-location-dot" style="margin-right:0.2rem;"></i>
                                        {{ $em->sede->nombreSede ?? '—' }}
                                        @if ($em->notas) · {{ $em->notas }} @endif
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </details>
        @endif
    </div>
    @endif

    {{-- 6. REGLAMENTO --}}
    @if ($torneo->reglamentoActual)
    @php $reg = $torneo->reglamentoActual; @endphp
    <div class="detail-grid-card">
        <p class="detail-grid-card-title"><i class="fa-solid fa-file-pdf"></i> Reglamento</p>
        <div class="reglamento-card">
            <i class="fa-solid fa-file-pdf reg-icon"></i>
            <div class="reglamento-card-info">
                <strong>{{ $reg->nombreArchivo }}</strong>
                <small>
                    {{ $reg->tamano_legible }} · subido el {{ $reg->created_at?->format('d/m/Y') }}
                    @if ($reg->subidoPor) · por {{ $reg->subidoPor->nombreUsuario }} @endif
                </small>
            </div>
            <button type="button" class="btn btn-primary btn-sm"
                    data-open-modal="ver-reglamento"
                    data-pdf-url="{{ asset('storage/' . $reg->rutaArchivo) }}"
                    data-pdf-name="{{ $reg->nombreArchivo }}">
                <i class="fa-solid fa-eye"></i>
                Ver PDF
            </button>
        </div>
    </div>
    @endif

</div>

{{-- ═══════════════ MODAL CAMBIO DE ESTADO DEL TORNEO ═══════════════ --}}
@can('editar-torneos')
<div class="modal" id="modal-cambio-estado-torneo" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog modal-dialog--fit">
        <form method="POST" action="{{ route('torneos.estado', $torneo->idTorneo) }}"
              data-confirm-submit
              data-confirm-title="¿Cambiar estado del torneo?"
              data-confirm-text="El estado del torneo cambiará. Esta acción puede afectar a los partidos en curso."
              data-confirm-btn="Sí, cambiar estado">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Cambiar estado del torneo</h3>
                <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="estadoNuevo" class="form-label">Nuevo estado <span class="req">*</span></label>
                    <select id="estadoNuevo" name="estadoNuevo" required class="form-select"
                            data-nova-select data-placeholder="— Selecciona —">
                        <option value="">— Selecciona —</option>
                        @foreach (['proximo' => 'Próximo', 'activo' => 'Activo', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'] as $val => $label)
                            @if ($val !== $torneo->estadoTorneo)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    <small class="field-hint">
                        Estado actual: <strong>{{ ucfirst($torneo->estadoTorneo) }}</strong>.
                        Finalizar o cancelar requiere rol ejecutivo.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar</button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- ═══════════ MODAL VER REGLAMENTO (iframe) ═══════════ --}}
@if ($torneo->reglamentoActual)
<div class="modal" id="modal-ver-reglamento" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-dialog modal-dialog--xl">
        <div class="modal-header">
            <h3 class="modal-title" id="pdf-name-label">
                <i class="fa-solid fa-file-pdf" style="color:#ef4444;margin-right:0.5rem;"></i>
                Reglamento
            </h3>
            <a id="pdf-open-tab" href="#" target="_blank" rel="noopener"
               class="btn btn-secondary btn-sm" style="margin-left:auto;margin-right:0.6rem;">
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
@endif

@endsection

@push('scripts')
    @vite(['resources/js/torneos/torneos.js'])
    <script>
        // Inyectar URL del PDF cuando se abre el modal
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
        // Vaciar iframe al cerrar
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
    </script>
@endpush
