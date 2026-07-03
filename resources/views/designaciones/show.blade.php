@extends('layouts.app')

@section('titulo', "{$partido->equipoLocal} vs {$partido->equipoVisitante}")
@section('seccion', 'Designaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Gestionar partido</span>
    </div>

    @php
        $estado     = $partido->estadoPartido;
        $esBorrador = $estado === 'borrador';
        $esCritico  = $estado === 'critico';
        $enCurso    = $estado === 'en_curso';
        $fechaHuman = $partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $esTerminal = in_array($estado, ['finalizado', 'cancelado']);
        $rolUsuario = Auth::user()->rolUsuario;
        $veHistorial = in_array($rolUsuario, ['designador', 'ejecutivo', 'superadmin'], true);
        $estadoMapa = ['borrador'=>'Borrador','programado'=>'Programado','confirmado'=>'Confirmado','critico'=>'Crítico','aplazado'=>'Aplazado','en_curso'=>'En curso','finalizado'=>'Finalizado','cancelado'=>'Cancelado'];
    @endphp

    {{-- Banner borrador / publicado --}}
    @if($esBorrador)
    <div class="banner-borrador">
        <i class="fa-solid fa-file-pen"></i>
        <div>
            <strong>BORRADOR — Este partido no es visible para los árbitros.</strong>
            <div class="banner-borrador__sub">Asigna los árbitros y publícalo para que reciban su notificación.</div>
        </div>
    </div>
    @elseif(!$esTerminal)
    <div class="banner-publicado">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>PUBLICADO — Los árbitros han sido notificados.</strong>
            <div class="banner-publicado__sub">Para modificar árbitros debes contactar al árbitro directamente. Solo el veedor puede cambiarse.</div>
        </div>
    </div>
    @endif

    {{-- Banner crítico --}}
    @if($esCritico)
    <div class="banner-critico">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <strong>PARTIDO CRÍTICO — Se requiere acción inmediata.</strong>
        Este partido tiene designaciones incompletas y la fecha ya pasó o está muy próxima.
    </div>
    @endif

    {{-- Header del partido --}}
    <div class="show-header">
        <div class="show-header__match">
            <h1 class="show-match-title">
                {{ $partido->equipoLocal }}
                <span class="show-vs">vs</span>
                {{ $partido->equipoVisitante }}
            </h1>
            <div class="show-meta-chips">
                <span><i class="fa-regular fa-calendar"></i> {{ ucfirst($fechaHuman) }}</span>
                <span><i class="fa-regular fa-clock"></i> {{ $partido->horaPartido }}</span>
                <span><i class="fa-solid fa-location-dot"></i> {{ $partido->sede?->nombreSede ?? '—' }}</span>
            </div>
        </div>
        <span class="partido-estado-badge partido-estado-badge--lg estado-{{ $estado }}">
            {{ $estadoMapa[$estado] ?? $estado }}
        </span>
    </div>

    {{-- Grid principal --}}
    <div class="show-grid">

        {{-- Columna izquierda: slots --}}
        <div class="show-col-main">

            <div class="show-section-title">
                <i class="fa-solid fa-user-tie"></i>
                Equipo arbitral
                <span class="show-formato-chip">{{ $partido->formato?->nombre ?? '—' }}</span>
            </div>

            @php
                // Numerar visualmente solo cuando el rol tiene más de un slot
                $conteoPorRol = $slots->groupBy('idRol')->map->count();
            @endphp

            @foreach($slots as $slot)
            @php
                $designacion = $slot->designacion;
                $multiple    = ($conteoPorRol[$slot->idRol] ?? 1) > 1;
                $nombreSlot  = $slot->rol?->nombre . ($multiple ? " {$slot->numeroSlot}" : '');
            @endphp
            <div class="slot-card {{ $designacion ? 'slot-card--' . $designacion->estadoDesignacion : 'slot-card--vacio' }}"
                 id="rol-card-{{ $slot->idRol }}-{{ $slot->numeroSlot }}"
                 data-slot="{{ $slot->numeroSlot }}"
                 data-rol="{{ $slot->idRol }}">
                <div class="slot-card__header">
                    <div class="slot-card__name">
                        <i class="fa-solid fa-circle-dot"></i>
                        {{ $nombreSlot }}
                    </div>
                    @if($esBorrador && $designacion && $designacion->estaPendiente())
                    <button class="btn btn-ghost btn-xs btn-quitar"
                            data-id="{{ $designacion->idDesignacion }}"
                            onclick="quitarDesignacion({{ $designacion->idDesignacion }}, {{ $slot->idRol }})">
                        <i class="fa-solid fa-xmark"></i> Quitar
                    </button>
                    @endif
                </div>

                @if($designacion)
                    {{-- Árbitro asignado --}}
                    <div class="slot-card__arbitro">
                        <div class="rol-arbitro-avatar">
                            {{ strtoupper(substr($designacion->arbitro?->usuario?->nombreUsuario ?? 'A', 0, 1)) }}
                        </div>
                        <div class="rol-arbitro-info">
                            <div class="rol-arbitro-nombre">{{ $designacion->arbitro?->usuario?->nombreUsuario }}</div>
                            <div class="rol-arbitro-meta">
                                {{ $designacion->arbitro?->codigoCarnet }}
                                · {{ $designacion->arbitro?->categoria?->nombreCategoria }}
                            </div>
                        </div>
                        <span class="desig-estado-pill desig-estado-{{ $designacion->estadoDesignacion }}">
                            @if($designacion->estaConfirmada()) <i class="fa-solid fa-check"></i> Confirmado
                            @elseif($designacion->estaRechazada()) <i class="fa-solid fa-xmark"></i> Rechazado
                            @else <i class="fa-regular fa-clock"></i> Pendiente
                            @endif
                        </span>
                    </div>
                    @if($designacion->estaRechazada() && $designacion->motivoRechazo)
                    <div class="rol-rechazo-motivo">
                        <i class="fa-solid fa-comment-slash"></i>
                        "{{ $designacion->motivoRechazo }}"
                    </div>
                    @endif
                @elseif($esBorrador)
                    @can('crear-designaciones')
                    {{-- Buscador de árbitro (solo en borrador) --}}
                    <div class="arbitro-search" data-rol="{{ $slot->idRol }}" data-partido="{{ $partido->idPartido }}">
                        <input type="text"
                               class="arbitro-search__input form-input"
                               placeholder="Buscar árbitro..."
                               autocomplete="off">
                        <div class="arbitro-search__results" style="display:none"></div>
                    </div>
                    @else
                    <div class="slot-card__bloqueado">
                        <i class="fa-solid fa-user-slash"></i> Sin asignar
                    </div>
                    @endcan
                @else
                    <div class="slot-card__bloqueado">
                        <i class="fa-solid fa-lock"></i>
                        Sin asignar — la asignación se bloquea al publicar
                    </div>
                @endif
            </div>
            @endforeach

            {{-- Historial — solo designador y ejecutivo, nunca árbitros --}}
            @if($veHistorial)
            <div class="show-section-title" style="margin-top:2rem">
                <i class="fa-solid fa-history"></i>
                Historial de acciones
            </div>
            <div class="historial-timeline">
                @forelse($partido->historial as $h)
                @php
                    $tipoLabel = [
                        'asignado'               => 'Árbitro asignado',
                        'confirmado'             => 'Árbitro confirmó',
                        'rechazado'              => 'Árbitro rechazó',
                        'quitado'                => 'Árbitro quitado',
                        'partido_creado'         => 'Partido creado',
                        'estado_partido_cambiado'=> 'Estado cambiado',
                        'emergente_cubrio'       => 'Emergente cubrió',
                    ][$h->tipoAccion] ?? $h->tipoAccion;
                @endphp
                <div class="historial-item">
                    <div class="historial-dot historial-dot--{{ $h->tipoAccion }}"></div>
                    <div class="historial-body">
                        <div class="historial-accion">{{ $tipoLabel }}</div>
                        @if($h->arbitro)
                        <div class="historial-arbitro">{{ $h->arbitro?->usuario?->nombreUsuario }}</div>
                        @endif
                        @if($h->estadoAnterior && $h->estadoNuevo)
                        <div class="historial-transicion">
                            <span class="estado-chip">{{ $h->estadoAnterior }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                            <span class="estado-chip estado-chip--nuevo">{{ $h->estadoNuevo }}</span>
                        </div>
                        @endif
                        @if($h->detalle)
                        <div class="historial-detalle">"{{ $h->detalle }}"</div>
                        @endif
                        <div class="historial-meta">
                            {{ $h->usuarioAccion?->nombreUsuario ?? 'Sistema' }}
                            · {{ $h->created_at?->locale('es')->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <p class="empty-state__sub">No hay acciones registradas aún.</p>
                @endforelse
            </div>
            @endif
        </div>

        {{-- Columna derecha: info y acciones --}}
        <div class="show-col-side">

            {{-- Publicar partido (solo borrador) --}}
            @can('crear-designaciones')
            @if($esBorrador)
            <div class="info-card info-card--publicar">
                <div class="info-card__title"><i class="fa-solid fa-rocket"></i> Publicación</div>
                <p class="publicar-nota">
                    Al publicar, los árbitros designados recibirán la notificación
                    y podrán confirmar o rechazar su designación.
                </p>
                <button class="btn-publicar" onclick="publicarPartido({{ $partido->idPartido }})">
                    <i class="fa-solid fa-rocket"></i>
                    Publicar partido
                </button>
            </div>
            @endif
            @endcan

            {{-- Info del partido --}}
            <div class="info-card">
                <div class="info-card__title">Información del partido</div>
                <div class="info-row"><span>Torneo</span><strong>{{ $partido->torneo?->nombreTorneo }}</strong></div>
                <div class="info-row"><span>División</span><strong>{{ $partido->division?->nombreDivision ?? '—' }}</strong></div>
                <div class="info-row"><span>Formato</span><strong>{{ $partido->formato?->nombre ?? '—' }}</strong></div>
                <div class="info-row"><span>Modalidad</span><strong>{{ $partido->modalidadPago ?? '—' }}</strong></div>
                <div class="info-row"><span>Sede</span><strong>{{ $partido->sede?->nombreSede ?? '—' }}</strong></div>
                <div class="info-row"><span>Municipio</span><strong>{{ $partido->sede?->municipio ?? '—' }}</strong></div>
                @if($partido->sede?->urlMaps)
                <a href="{{ $partido->sede->urlMaps }}" target="_blank" class="btn btn-ghost btn-sm" style="width:100%;margin-top:.75rem;justify-content:center">
                    <i class="fa-solid fa-map-location-dot"></i> Ver en Maps
                </a>
                @endif
                @if($partido->observaciones)
                <div class="info-obs">
                    <div class="info-obs__label">Observaciones</div>
                    <div class="info-obs__text">{{ $partido->observaciones }}</div>
                </div>
                @endif
            </div>

            {{-- Cambiar estado (publicado, no terminal) --}}
            @can('crear-designaciones')
            @if(!$esBorrador && !$esTerminal)
            <div class="info-card">
                <div class="info-card__title">Cambiar estado</div>
                <select id="estado-nuevo" class="form-input" data-nova-select>
                    @foreach(\App\StateMachines\PartidoStateMachine::TRANSICIONES[$estado] as $sig)
                    <option value="{{ $sig }}">{{ $estadoMapa[$sig] ?? $sig }}</option>
                    @endforeach
                </select>
                <textarea id="estado-detalle" class="form-input" rows="2"
                          placeholder="Observación (opcional)..." style="margin-top:.75rem;resize:vertical"></textarea>
                <button class="btn btn-primary" style="width:100%;margin-top:.75rem"
                        onclick="cambiarEstado({{ $partido->idPartido }}, {{ $partido->version }})">
                    <i class="fa-solid fa-arrows-rotate"></i> Aplicar cambio
                </button>
            </div>
            @endif
            @endcan

            {{-- Revertir finalizado — solo ejecutivo --}}
            @if($estado === 'finalizado' && in_array($rolUsuario, ['ejecutivo', 'superadmin'], true))
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-rotate-left"></i> Reversión</div>
                <p class="publicar-nota">
                    Como ejecutivo puedes revertir este partido finalizado a programado.
                </p>
                <button class="btn btn-secondary" style="width:100%"
                        onclick="revertirFinalizado({{ $partido->idPartido }}, {{ $partido->version }})">
                    <i class="fa-solid fa-rotate-left"></i> Revertir a programado
                </button>
            </div>
            @endif

            {{-- Veedor — editable incluso publicado --}}
            @can('crear-designaciones')
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-eye"></i> Veedor</div>
                @if($partido->veedor)
                <div class="arbitro-item" style="margin-bottom:.75rem">
                    <div class="arbitro-avatar">
                        {{ strtoupper(substr($partido->veedor->nombreUsuario ?? 'V', 0, 2)) }}
                    </div>
                    <div style="flex:1">
                        <div style="font-weight:600;color:var(--text-primary)">{{ $partido->veedor->nombreUsuario }}</div>
                        <div style="font-size:.78rem;color:var(--text-muted)">{{ $partido->veedor->rolUsuario }}</div>
                    </div>
                </div>
                @else
                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.75rem">Sin veedor asignado.</p>
                @endif

                @if(!$esTerminal)
                <select id="veedor-select" class="form-input" data-nova-select data-placeholder="Selecciona un veedor...">
                    <option value="">Sin veedor</option>
                    @foreach($posiblesVeedores as $pv)
                    <option value="{{ $pv->idUsuario }}" {{ $partido->idVeedor == $pv->idUsuario ? 'selected' : '' }}>
                        {{ $pv->nombreUsuario }} ({{ $pv->rolUsuario }})
                    </option>
                    @endforeach
                </select>
                <button class="btn btn-secondary btn-sm" style="width:100%;margin-top:.5rem"
                        onclick="asignarVeedor({{ $partido->idPartido }})">
                    <i class="fa-solid fa-user-check"></i>
                    {{ $partido->veedor ? 'Cambiar veedor' : 'Asignar veedor' }}
                </button>
                @endif
            </div>
            @endcan

            {{-- Acta PDF --}}
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-file-pdf"></i> Documentos</div>
                <a href="{{ route('designaciones.partido.acta', $partido->idPartido) }}"
                   target="_blank"
                   class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-download"></i> Descargar acta PDF
                </a>
            </div>

            {{-- Calificaciones --}}
            @can('crear-calificaciones')
            @if($estado === 'finalizado')
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-star"></i> Calificaciones</div>
                <a href="{{ route('designaciones.calificaciones.index', $partido->idPartido) }}"
                   class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-star-half-stroke"></i> Calificar árbitros
                </a>
            </div>
            @endif
            @endcan

        </div>
    </div>

</div>

<script>
window.colegioId   = {{ Auth::user()->idColegio }};
window.broadcastAuthEndpoint = "{{ url('/broadcasting/auth') }}";
window.partidoId   = {{ $partido->idPartido }};
window.partidoVersion = {{ $partido->version }};
window.asignarUrl  = "{{ route('designaciones.asignar', $partido->idPartido) }}";
window.quitarBase  = "{{ url('/designaciones/designacion') }}";
window.estadoUrl   = "{{ route('designaciones.estado', $partido->idPartido) }}";
window.buscarUrl   = "{{ route('api.partidos.arbitros-disponibles', $partido->idPartido) }}";
window.veedorUrl   = "{{ route('designaciones.partido.veedor', $partido->idPartido) }}";
window.publicarUrl = "{{ route('designaciones.partido.publicar', $partido->idPartido) }}";
window.csrfToken   = "{{ csrf_token() }}";
</script>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
