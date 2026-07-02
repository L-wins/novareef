@extends('layouts.app')

@section('titulo', "{$partido->equipoLocal} vs {$partido->equipoVisitante}")
@section('seccion', 'Mis Partidos')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('mis-partidos.index') }}">Mis Partidos</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Detalle del partido</span>
    </div>

    @php
        $estado     = $partido->estadoPartido;
        $estadoMapa = ['programado'=>'Programado','confirmado'=>'Confirmado','critico'=>'Crítico','aplazado'=>'Aplazado','en_curso'=>'En curso','finalizado'=>'Finalizado','cancelado'=>'Cancelado'];
        $fechaHuman = $partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $esHoy      = $partido->fechaPartido?->isToday();
        $esOficial  = $partido->torneo?->tipoTorneo === 'oficial';
    @endphp

    {{-- Hero del partido --}}
    <div class="show-header" style="margin-bottom:2rem;padding:2rem;background:var(--bg-card);border-radius:16px;border:1px solid var(--border-color)">
        <div class="show-header__match">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem">
                {{ $partido->torneo?->nombreTorneo }}
                @if($partido->division) · {{ $partido->division->nombreDivision }}@endif
            </div>
            <h1 class="show-match-title" style="font-size:clamp(1.4rem,4vw,2.2rem)">
                {{ $partido->equipoLocal }}
                <span class="show-vs">vs</span>
                {{ $partido->equipoVisitante }}
            </h1>
            <div class="show-meta-chips" style="margin-top:.75rem">
                <span><i class="fa-regular fa-calendar"></i> {{ ucfirst($fechaHuman) }}</span>
                <span><i class="fa-regular fa-clock"></i> {{ $partido->horaPartido }}</span>
                <span><i class="fa-solid fa-location-dot"></i> {{ $partido->sede?->nombreSede ?? '—' }}</span>
            </div>
            @if($esHoy)
            <div class="countdown-timer urgente" id="detalle-countdown" style="margin-top:1rem;font-size:1.8rem"></div>
            @endif
        </div>
        <div style="display:flex;flex-direction:column;gap:.5rem;align-items:flex-end">
            <span class="partido-estado-badge partido-estado-badge--lg estado-{{ $estado }}">
                {{ $estadoMapa[$estado] ?? $estado }}
            </span>
            <span class="mis-rol-chip">
                <i class="fa-solid fa-user-tie"></i>
                Mi rol: <strong>{{ $designacion->rol?->nombre }}</strong>
            </span>
            @if($esCentral && $estado === 'en_curso')
            <button class="btn-finalizar" onclick="finalizarPartido({{ $partido->idPartido }})">
                <i class="fa-solid fa-flag-checkered"></i> Finalizar partido
            </button>
            @endif
        </div>
    </div>

    {{-- Recordatorio COMET --}}
    @if($esOficial)
    <div class="mis-comet-reminder" style="margin-bottom:1.5rem;border-radius:12px">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <strong>Recuerda registrar este partido en COMET una vez finalizado.</strong>
            <div style="font-size:.82rem;margin-top:2px">
                Torneo oficial — el registro en COMET es obligatorio.
            </div>
        </div>
    </div>
    @endif

    {{-- Grid 2 columnas --}}
    <div class="show-grid">

        {{-- Columna izquierda --}}
        <div class="show-col-main">

            {{-- Mi designación --}}
            <div class="info-card" style="margin-bottom:1.25rem">
                <div class="info-card__title"><i class="fa-solid fa-id-card"></i> Mi designación</div>
                <div class="info-row">
                    <span>Mi rol</span>
                    <strong style="color:var(--accent);font-size:1.05rem">{{ $designacion->rol?->nombre }}</strong>
                </div>
                <div class="info-row">
                    <span>Estado</span>
                    <span class="partido-estado-badge estado-{{ $designacion->estadoDesignacion }}" style="font-size:.8rem">
                        @if($designacion->estaConfirmada()) <i class="fa-solid fa-check"></i> Confirmada
                        @else {{ ucfirst($designacion->estadoDesignacion) }}
                        @endif
                    </span>
                </div>
                @if($designacion->fechaConfirmacion)
                <div class="info-row">
                    <span>Confirmado el</span>
                    <strong>{{ $designacion->fechaConfirmacion->locale('es')->isoFormat('D [de] MMMM') }}</strong>
                </div>
                @endif
            </div>

            {{-- Equipo arbitral — slots del partido --}}
            <div class="info-card" style="margin-bottom:1.25rem">
                <div class="info-card__title"><i class="fa-solid fa-users"></i> Equipo arbitral</div>
                @php $conteoPorRol = $slots->groupBy('idRol')->map->count(); @endphp
                @forelse($slots as $slot)
                @php
                    $d         = $slot->designacion;
                    $esMio     = $d && $d->idDesignacion === $designacion->idDesignacion;
                    $multiple  = ($conteoPorRol[$slot->idRol] ?? 1) > 1;
                    $rolLabel  = $slot->rol?->nombre . ($multiple ? " {$slot->numeroSlot}" : '');
                @endphp
                <div class="arbitro-item {{ $esMio ? 'es-mi-designacion' : '' }}"
                     style="{{ $esMio ? 'background:rgba(79,142,247,.08);border-radius:8px' : '' }}">
                    <div class="arbitro-avatar">
                        {{ strtoupper(substr($d?->arbitro?->usuario?->nombreUsuario ?? '—', 0, 2)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:.9rem;color:var(--text-primary)">
                            {{ $d?->arbitro?->usuario?->nombreUsuario ?? 'Sin asignar' }}
                            @if($esMio) <span style="color:var(--accent);font-size:.75rem">(Yo)</span>@endif
                        </div>
                        <div style="font-size:.78rem;color:var(--text-secondary)">
                            {{ $rolLabel }}
                            @if($d?->arbitro?->categoria) · {{ $d->arbitro->categoria->nombreCategoria }}@endif
                        </div>
                        @if($d?->arbitro?->usuario?->telefonoUsuario)
                        <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                            <i class="fa-solid fa-phone" style="font-size:.7rem"></i>
                            {{ $d->arbitro->usuario->telefonoUsuario }}
                        </div>
                        @endif
                    </div>
                    @if($d)
                    <span class="partido-estado-badge estado-{{ $d->estadoDesignacion }}" style="font-size:.7rem;flex-shrink:0">
                        @if($d->estaConfirmada()) ✓
                        @elseif($d->estaRechazada()) ✗
                        @else ⏳
                        @endif
                    </span>
                    @endif
                </div>
                @empty
                <p style="color:var(--text-muted);font-size:.85rem">No hay árbitros designados aún.</p>
                @endforelse
            </div>

            {{-- Veedor --}}
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-eye"></i> Veedor</div>
                @if($partido->veedor)
                <div class="arbitro-item">
                    <div class="arbitro-avatar">
                        {{ strtoupper(substr($partido->veedor->nombreUsuario ?? 'V', 0, 2)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:.9rem;color:var(--text-primary)">
                            {{ $partido->veedor->nombreUsuario }}
                        </div>
                        @if($partido->veedor->telefonoUsuario)
                        <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                            <i class="fa-solid fa-phone" style="font-size:.7rem"></i>
                            {{ $partido->veedor->telefonoUsuario }}
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <p style="color:var(--text-muted);font-size:.85rem">Sin veedor asignado.</p>
                @endif
            </div>

        </div>

        {{-- Columna derecha --}}
        <div class="show-col-side">

            {{-- Datos del partido --}}
            <div class="info-card" style="margin-bottom:1.25rem">
                <div class="info-card__title"><i class="fa-solid fa-circle-info"></i> Datos del partido</div>
                <div class="info-row"><span>Torneo</span><strong>{{ $partido->torneo?->nombreTorneo }}</strong></div>
                <div class="info-row"><span>División</span><strong>{{ $partido->division?->nombreDivision ?? '—' }}</strong></div>
                <div class="info-row"><span>Formato</span><strong>{{ $partido->formato?->nombre ?? '—' }}</strong></div>
                <div class="info-row"><span>Modalidad</span><strong>{{ $partido->modalidadPago ?? '—' }}</strong></div>
                <div class="info-row"><span>Sede</span><strong>{{ $partido->sede?->nombreSede ?? '—' }}</strong></div>
                @if($partido->sede?->municipio)
                <div class="info-row"><span>Municipio</span><strong>{{ $partido->sede->municipio }}</strong></div>
                @endif
                @if($partido->sede?->urlMaps)
                <a href="{{ $partido->sede->urlMaps }}" target="_blank"
                   class="btn btn-ghost btn-sm" style="width:100%;margin-top:.75rem;justify-content:center">
                    <i class="fa-solid fa-map-location-dot"></i> Ver en Maps
                </a>
                @endif
            </div>

            {{-- Mi compensación --}}
            <div class="info-card pago-card" style="margin-bottom:1.25rem">
                <div class="info-card__title"><i class="fa-solid fa-hand-holding-dollar"></i> Mi compensación</div>
                @if(($pago['modalidad'] ?? null) === 'nomina')
                <div class="pago-card__body">
                    <div class="pago-card__valor pago-card__valor--nomina">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Nómina
                    </div>
                    <div class="pago-card__nota">Incluido en tu nómina mensual.</div>
                </div>
                @elseif(($pago['valor'] ?? null) !== null)
                <div class="pago-card__body">
                    <div class="pago-card__valor">
                        ${{ number_format($pago['valor'], 0, ',', '.') }} <span class="pago-card__moneda">COP</span>
                    </div>
                    <div class="pago-card__nota">
                        <i class="fa-solid fa-coins"></i> Pago en campo
                    </div>
                </div>
                @else
                <div class="pago-card__body">
                    <div class="pago-card__nota" style="padding:.5rem 0">
                        <i class="fa-regular fa-circle-question"></i>
                        Tarifa no definida — consultar con el designador.
                    </div>
                </div>
                @endif
            </div>

            {{-- Mi calificación --}}
            <div class="info-card">
                <div class="info-card__title"><i class="fa-solid fa-star"></i> Mi calificación</div>
                @if($designacion->calificacion)
                @php $cal = $designacion->calificacion; @endphp
                <div style="text-align:center;padding:1rem 0">
                    <div class="nota-stars" style="font-size:2rem;margin-bottom:.5rem">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor((float)$cal->nota)) ★
                            @elseif($i - (float)$cal->nota <= 0.5) ½
                            @else ☆
                            @endif
                        @endfor
                    </div>
                    <div style="font-size:2.5rem;font-weight:900;color:var(--text-primary)">
                        {{ number_format((float)$cal->nota, 1) }}
                    </div>
                    <div style="margin:.5rem 0">
                        <span class="partido-estado-badge estado-{{ $cal->notaColor === 'green' ? 'confirmado' : ($cal->notaColor === 'blue' ? 'programado' : ($cal->notaColor === 'yellow' ? 'critico' : 'cancelado')) }}">
                            {{ $cal->notaLabel }}
                        </span>
                    </div>
                    <div style="font-size:.85rem;color:var(--text-secondary);font-style:italic;margin-top:.75rem">
                        "{{ $cal->comentario }}"
                    </div>
                </div>
                @else
                <div style="text-align:center;padding:1.5rem 0;color:var(--text-muted)">
                    <i class="fa-regular fa-star" style="font-size:2rem;margin-bottom:.75rem;display:block"></i>
                    Aún no has sido calificado para este partido.
                </div>
                @endif
            </div>

        </div>
    </div>

</div>

<script>
window.csrfToken     = "{{ csrf_token() }}";
window.finalizarBase = "{{ url('/designaciones/partido') }}";
</script>

@if($esHoy)
<script>
(function () {
    const hora    = "{{ $partido->horaPartido }}";
    const fecha   = "{{ $partido->fechaPartido->format('Y-m-d') }}";
    const partes  = hora.substring(0,5).split(':');
    const objetivo= new Date(fecha + 'T' + partes[0].padStart(2,'0') + ':' + partes[1].padStart(2,'0') + ':00');
    const timer   = document.getElementById('detalle-countdown');

    function tick() {
        const diff = objetivo - new Date();
        if (!timer || diff <= 0) { if (timer) timer.textContent = ''; return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        timer.textContent = (h > 0 ? h + 'h ' : '') + m + 'min ' + s + 's';
        timer.classList.toggle('urgente', diff < 3600000);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endif
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
