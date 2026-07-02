@extends('layouts.app')

@section('titulo', 'Mis Partidos')
@section('seccion', 'Mis Partidos')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@php
    /**
     * Chip de compensación según modalidad de pago y tarifa.
     * campo + tarifa → valor COP · nomina → nómina · sin tarifa → consultar
     */
    $renderPago = function ($desig) {
        $pago = $desig->pago ?? ['valor' => null, 'modalidad' => null];

        if (($pago['modalidad'] ?? null) === 'nomina') {
            return '<span class="pago-chip pago-chip--nomina"><i class="fa-solid fa-file-invoice-dollar"></i> Nómina</span>';
        }

        if ($pago['valor'] !== null) {
            $valor = '$' . number_format($pago['valor'], 0, ',', '.') . ' COP';
            return '<span class="pago-chip pago-chip--campo"><i class="fa-solid fa-coins"></i> ' . $valor . '</span>';
        }

        return '<span class="pago-chip pago-chip--consultar"><i class="fa-regular fa-circle-question"></i> Consultar con designador</span>';
    };
@endphp

@section('contenido')
<div class="container">

    {{-- ═══ HERO ═══ --}}
    <div class="mis-hero">
        <div class="mis-hero__left">
            <div class="mis-hero__label">Panel del árbitro</div>
            <h1 class="mis-hero__saludo">
                Hola, {{ \Illuminate\Support\Str::of($arbitro->usuario?->nombreUsuario ?? 'Árbitro')->before(' ') }}
            </h1>
            <p class="mis-hero__sub">Tus designaciones activas e historial de partidos.</p>
        </div>
        <div class="mis-hero__stats">
            <div class="mis-stat {{ $hoyPartidos->isNotEmpty() ? 'mis-stat--hoy' : '' }}">
                <span class="mis-stat__num">{{ $hoyPartidos->count() }}</span>
                <span class="mis-stat__label">Hoy</span>
            </div>
            <div class="mis-stat {{ $pendientesCount > 0 ? 'mis-stat--pendiente' : '' }}">
                <span class="mis-stat__num">{{ $pendientesCount }}</span>
                <span class="mis-stat__label">Por confirmar</span>
            </div>
            <div class="mis-stat">
                <span class="mis-stat__num">{{ $proximos->count() + $mananaPartidos->count() }}</span>
                <span class="mis-stat__label">Próximos</span>
            </div>
        </div>
    </div>

    {{-- ═══ HOY ═══ --}}
    @if($hoyPartidos->isNotEmpty())
    <div class="mis-partidos-section mis-partidos-section--hoy">
        <div class="mis-section-label mis-section-label--hoy">
            <i class="fa-solid fa-circle" style="font-size:.5rem;color:#ef4444;animation:pulso 1.2s infinite"></i>
            HOY
        </div>

        @foreach($hoyPartidos as $desig)
        @php
            $partido = $desig->partido;
            $totalDesig = $partido->designaciones->count();
            $todosConfirmados = $totalDesig > 0 && $partido->designaciones->every(fn($d) => $d->estaConfirmada());
            $soyCentral = $desig->rol?->nombre === 'Central';
            $enCurso = $partido->estadoPartido === 'en_curso';
        @endphp
        <div class="mis-partido-card mis-partido-card--hoy"
             id="desig-card-{{ $desig->idDesignacion }}"
             data-fecha-partido="{{ $partido->fechaPartido->format('Y-m-d') }}"
             data-hora-partido="{{ $partido->horaPartido }}">

            <div class="mis-partido-card__topbar">
                <span class="etiqueta-dinamica" data-fecha="{{ $partido->fechaPartido->format('Y-m-d') }}"></span>
                <span class="countdown-timer" id="countdown-{{ $desig->idDesignacion }}"></span>
                <span class="partido-estado-badge estado-{{ $desig->estadoDesignacion }}">
                    @if($desig->estaConfirmada()) <i class="fa-solid fa-check"></i> Confirmado
                    @elseif($desig->estaRechazada()) <i class="fa-solid fa-xmark"></i> Rechazado
                    @else <i class="fa-regular fa-clock"></i> Pendiente
                    @endif
                </span>
            </div>

            <div class="mis-match-title">
                {{ $partido->equipoLocal }} <span class="show-vs">vs</span> {{ $partido->equipoVisitante }}
            </div>

            <div class="mis-meta-grid">
                <div><i class="fa-regular fa-clock"></i> {{ $partido->horaPartido }}</div>
                <div><i class="fa-solid fa-location-dot"></i> {{ $partido->sede?->nombreSede ?? '—' }}{{ $partido->sede?->municipio ? ', '.$partido->sede->municipio : '' }}</div>
                <div><i class="fa-solid fa-trophy"></i> {{ $partido->torneo?->nombreTorneo }}</div>
                <div><i class="fa-solid fa-user-tie"></i> Mi rol: <strong>{{ $desig->rol?->nombre ?? 'Árbitro' }}</strong></div>
            </div>

            <div class="mis-card-acciones">
                {!! $renderPago($desig) !!}

                @if($todosConfirmados)
                <a href="{{ route('mis-partidos.detalle', $partido->idPartido) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Gestionar partido
                </a>
                @endif

                @if($soyCentral && $enCurso && $desig->estaConfirmada())
                <button class="btn-finalizar" onclick="finalizarPartido({{ $partido->idPartido }})">
                    <i class="fa-solid fa-flag-checkered"></i> Finalizar partido
                </button>
                @endif

                @if($partido->sede?->urlMaps)
                <a href="{{ $partido->sede->urlMaps }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-map-location-dot"></i> Maps
                </a>
                @endif
            </div>

            @if($partido->torneo?->tipoTorneo === 'oficial')
            <div class="mis-comet-reminder">
                <i class="fa-solid fa-star"></i>
                <strong>RECORDATORIO COMET:</strong> Torneo oficial — regístrate en el sistema antes del partido.
            </div>
            @endif

            @if($desig->estaPendiente())
            <div class="mis-acciones">
                <button class="btn btn-success btn-lg"
                        onclick="confirmarDesignacion({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-check"></i> Confirmar asistencia
                </button>
                <button class="btn btn-danger btn-lg"
                        onclick="abrirModalRechazo({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-xmark"></i> No puedo asistir
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══ MAÑANA ═══ --}}
    @if($mananaPartidos->isNotEmpty())
    <div class="mis-partidos-section mis-partidos-section--manana">
        <div class="mis-section-label mis-section-label--manana">
            <i class="fa-solid fa-sun"></i>
            MAÑANA
        </div>

        @foreach($mananaPartidos as $desig)
        @php
            $partido = $desig->partido;
            $totalDesig = $partido->designaciones->count();
            $todosConfirmados = $totalDesig > 0 && $partido->designaciones->every(fn($d) => $d->estaConfirmada());
        @endphp
        <div class="mis-partido-card mis-partido-card--manana"
             id="desig-card-{{ $desig->idDesignacion }}"
             data-fecha-partido="{{ $partido->fechaPartido->format('Y-m-d') }}">

            <div class="mis-partido-card__topbar">
                <span class="etiqueta-dinamica" data-fecha="{{ $partido->fechaPartido->format('Y-m-d') }}"></span>
                <span class="partido-estado-badge estado-{{ $desig->estadoDesignacion }}">
                    @if($desig->estaConfirmada()) <i class="fa-solid fa-check"></i> Confirmado
                    @elseif($desig->estaRechazada()) <i class="fa-solid fa-xmark"></i> Rechazado
                    @else <i class="fa-regular fa-clock"></i> Pendiente
                    @endif
                </span>
            </div>

            <div class="mis-match-title">
                {{ $partido->equipoLocal }} <span class="show-vs">vs</span> {{ $partido->equipoVisitante }}
            </div>

            <div class="mis-meta-grid">
                <div><i class="fa-regular fa-clock"></i> {{ $partido->horaPartido }}</div>
                <div><i class="fa-solid fa-location-dot"></i> {{ $partido->sede?->nombreSede ?? '—' }}</div>
                <div><i class="fa-solid fa-trophy"></i> {{ $partido->torneo?->nombreTorneo }}</div>
                <div><i class="fa-solid fa-user-tie"></i> Mi rol: <strong>{{ $desig->rol?->nombre ?? 'Árbitro' }}</strong></div>
            </div>

            @if($desig->estaPendiente())
            <div class="mis-acciones mis-acciones--compact">
                <button class="btn btn-success btn-sm"
                        onclick="confirmarDesignacion({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-check"></i> Confirmar
                </button>
                <button class="btn btn-danger btn-sm"
                        onclick="abrirModalRechazo({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-xmark"></i> No puedo
                </button>
            </div>
            @endif

            <div class="mis-card-acciones">
                {!! $renderPago($desig) !!}

                @if($todosConfirmados)
                <a href="{{ route('mis-partidos.detalle', $partido->idPartido) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Gestionar partido
                </a>
                @endif
                @if($partido->sede?->urlMaps)
                <a href="{{ $partido->sede->urlMaps }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-map-location-dot"></i> Maps
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══ PRÓXIMOS ═══ --}}
    @if($proximos->isNotEmpty())
    <div class="mis-partidos-section">
        <div class="mis-section-label">
            <i class="fa-solid fa-calendar-days"></i>
            Próximos partidos
        </div>

        @foreach($proximos as $desig)
        @php
            $partido = $desig->partido;
            $totalDesig = $partido->designaciones->count();
            $todosConfirmados = $totalDesig > 0 && $partido->designaciones->every(fn($d) => $d->estaConfirmada());
        @endphp
        <div class="mis-partido-card"
             id="desig-card-{{ $desig->idDesignacion }}"
             data-fecha-partido="{{ $partido->fechaPartido->format('Y-m-d') }}">

            <div class="mis-partido-card__header">
                <div>
                    <div class="mis-match-name">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</div>
                    <div class="mis-match-meta">
                        <span><i class="fa-regular fa-calendar"></i> {{ ucfirst($partido->fechaPartido->locale('es')->isoFormat('D [de] MMMM')) }}</span>
                        <span><i class="fa-regular fa-clock"></i> {{ $partido->horaPartido }}</span>
                        <span><i class="fa-solid fa-user-tie"></i> {{ $desig->rol?->nombre }}</span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.4rem">
                    <span class="etiqueta-dinamica" data-fecha="{{ $partido->fechaPartido->format('Y-m-d') }}"></span>
                    <span class="partido-estado-badge estado-{{ $desig->estadoDesignacion }}" style="font-size:.78rem">
                        @if($desig->estaConfirmada()) ✓ Confirmado
                        @elseif($desig->estaRechazada()) ✗ Rechazado
                        @else ⏳ Pendiente
                        @endif
                    </span>
                </div>
            </div>

            @if($desig->estaPendiente())
            <div class="mis-acciones mis-acciones--compact">
                <button class="btn btn-success btn-sm"
                        onclick="confirmarDesignacion({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-check"></i> Confirmar
                </button>
                <button class="btn btn-danger btn-sm"
                        onclick="abrirModalRechazo({{ $desig->idDesignacion }})">
                    <i class="fa-solid fa-xmark"></i> No puedo
                </button>
            </div>
            @endif

            <div class="mis-card-acciones">
                {!! $renderPago($desig) !!}

                @if($todosConfirmados)
                <a href="{{ route('mis-partidos.detalle', $partido->idPartido) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Gestionar partido
                </a>
                @endif
                @if($partido->sede?->urlMaps)
                <a href="{{ $partido->sede->urlMaps }}" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-map-location-dot"></i> Maps
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══ HISTORIAL ═══ --}}
    @if($historial->isNotEmpty())
    <div class="mis-partidos-section">
        <div class="mis-section-label">
            <i class="fa-solid fa-history"></i>
            Historial
        </div>
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Partido</th>
                        <th>Fecha</th>
                        <th>Rol</th>
                        <th>Pago</th>
                        <th>Mi estado</th>
                        <th>Estado partido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historial as $desig)
                    @php $partido = $desig->partido; @endphp
                    <tr>
                        <td>{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</td>
                        <td>{{ $partido->fechaPartido?->locale('es')->isoFormat('D/M/YYYY') }}</td>
                        <td>{{ $desig->rol?->nombre ?? '—' }}</td>
                        <td>{!! $renderPago($desig) !!}</td>
                        <td>
                            <span class="partido-estado-badge estado-{{ $desig->estadoDesignacion }}" style="font-size:.72rem">
                                {{ ucfirst($desig->estadoDesignacion) }}
                            </span>
                        </td>
                        <td>
                            <span class="partido-estado-badge estado-{{ $partido->estadoPartido }}" style="font-size:.72rem">
                                {{ ucfirst(str_replace('_', ' ', $partido->estadoPartido)) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($hoyPartidos->isEmpty() && $mananaPartidos->isEmpty() && $proximos->isEmpty() && $historial->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-futbol" style="font-size:2.5rem;color:var(--text-muted);margin-bottom:1rem"></i>
        <p class="empty-state__title">Sin designaciones</p>
        <p class="empty-state__sub">Cuando el designador te asigne a un partido, aparecerá aquí.</p>
    </div>
    @endif

</div>

{{-- Modal de rechazo --}}
<div id="modal-rechazo" class="nova-modal-overlay" style="display:none" role="dialog" aria-modal="true">
    <div class="nova-modal">
        <div class="nova-modal__header">
            <h2>No puedo asistir</h2>
            <button class="nova-modal__close" onclick="cerrarModalRechazo()" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="nova-modal__body">
            <p style="color:var(--text-secondary);margin-bottom:1rem">
                Por favor indica el motivo por el que no puedes asistir (mín. 10 caracteres).
            </p>
            <textarea id="rechazo-motivo" class="form-input" rows="4"
                      maxlength="300" placeholder="Describe el motivo..."
                      style="resize:vertical"></textarea>
            <div class="form-hint text-right"><span id="rechazo-counter">0</span>/300</div>
        </div>
        <div class="nova-modal__footer">
            <button class="btn btn-ghost" onclick="cerrarModalRechazo()">Cancelar</button>
            <button class="btn btn-danger" id="btn-confirmar-rechazo">
                <i class="fa-solid fa-xmark"></i> Confirmar rechazo
            </button>
        </div>
    </div>
</div>

<script>
window.colegioId     = {{ Auth::user()->idColegio }};
window.csrfToken     = "{{ csrf_token() }}";
window.confirmarBase = "{{ url('/mis-partidos') }}";
window.rechazarBase  = "{{ url('/mis-partidos') }}";
window.finalizarBase = "{{ url('/designaciones/partido') }}";

// ── Etiquetas dinámicas ──────────────────────────────────────────────────────
function calcularEtiqueta(fechaPartido) {
    const hoy    = new Date(); hoy.setHours(0,0,0,0);
    const partido= new Date(fechaPartido + 'T00:00:00');
    const diff   = Math.round((partido - hoy) / (1000*60*60*24));
    if (diff === 0) return { texto: '🔴 HOY',      clase: 'etiqueta-hoy',    urgente: true  };
    if (diff === 1) return { texto: '⚡ MAÑANA',   clase: 'etiqueta-manana', urgente: true  };
    if (diff === 2) return { texto: '📅 En 2 días',clase: 'etiqueta-pronto', urgente: false };
    if (diff <  0) return { texto: 'Pasado',       clase: 'etiqueta-pasado', urgente: false };
    return { texto: 'En ' + diff + ' días', clase: 'etiqueta-futuro', urgente: false };
}

function actualizarEtiquetas() {
    document.querySelectorAll('.etiqueta-dinamica').forEach(function (el) {
        const fecha = el.dataset.fecha;
        if (!fecha) return;
        const e = calcularEtiqueta(fecha);
        el.textContent = e.texto;
        el.className = 'etiqueta-dinamica ' + e.clase;
    });
}

// ── Countdown HOY ───
function actualizarCountdowns() {
    document.querySelectorAll('[data-hora-partido]').forEach(function (card) {
        const fecha = card.dataset.fechaPartido;
        const hora  = card.dataset.horaPartido;
        if (!fecha || !hora) return;

        const desigId = card.id.replace('desig-card-','');
        const timer   = document.getElementById('countdown-' + desigId);
        if (!timer) return;

        const partes  = hora.substring(0,5).split(':');
        const objetivo= new Date(fecha + 'T' + partes[0].padStart(2,'0') + ':' + partes[1].padStart(2,'0') + ':00');
        const ahora   = new Date();
        const diffMs  = objetivo - ahora;

        if (diffMs <= 0) {
            timer.textContent = '';
            return;
        }

        const horas   = Math.floor(diffMs / 3600000);
        const minutos = Math.floor((diffMs % 3600000) / 60000);

        timer.textContent = horas > 0
            ? 'Faltan ' + horas + 'h ' + minutos + 'min'
            : 'Faltan ' + minutos + ' min';
        timer.classList.toggle('urgente', diffMs < 3600000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    actualizarEtiquetas();
    actualizarCountdowns();
    setInterval(actualizarEtiquetas,   60000);
    setInterval(actualizarCountdowns,  1000);
});
</script>
@endsection

@push('scripts')
    @vite(['resources/js/designaciones/designaciones.js'])
@endpush
