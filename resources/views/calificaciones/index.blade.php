@extends('layouts.app')

@section('titulo', "Calificaciones — {$partido->equipoLocal} vs {$partido->equipoVisitante}")
@section('seccion', 'Calificaciones')

@push('styles')
    @vite(['resources/css/designaciones/designaciones.css'])
@endpush

@section('contenido')
<div class="container">

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
        <a href="{{ route('designaciones.index') }}">Designaciones</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="{{ route('designaciones.index', ['torneo' => $partido->idTorneo]) }}">{{ $partido->torneo->nombreTorneo }}</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="{{ route('designaciones.show', $partido->idPartido) }}">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Calificaciones</span>
    </div>

    {{-- Hero --}}
    <div class="desig-hero" style="margin-bottom:2rem">
        <div>
            <span class="desig-hero__label">Evaluación arbitral</span>
            <h1 class="desig-hero__title">
                {{ $partido->equipoLocal }}
                <span style="color:var(--text-muted);font-weight:400">vs</span>
                {{ $partido->equipoVisitante }}
            </h1>
            <p class="desig-hero__subtitle">
                {{ ucfirst($partido->fechaPartido->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
                · {{ $partido->torneo?->nombreTorneo }}
            </p>
        </div>
        <a href="{{ route('designaciones.show', $partido->idPartido) }}" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Volver al partido
        </a>
    </div>

    @if($partido->estadoPartido !== 'finalizado')
    <div class="banner-critico" style="margin-bottom:1.5rem">
        <i class="fa-solid fa-circle-info"></i>
        Las calificaciones solo se habilitan cuando el partido está <strong>finalizado</strong>.
        Estado actual: <strong>{{ $partido->estadoPartido }}</strong>
    </div>
    @endif

    {{-- Cards de árbitros --}}
    <div style="display:flex;flex-direction:column;gap:1.25rem">
        @forelse($partido->designaciones as $designacion)
        @php
            $arbitro = $designacion->arbitro;
            $usuario = $arbitro?->usuario;
            $cal     = $designacion->calificacion;
            $iniciales = strtoupper(substr($usuario?->nombreUsuario ?? 'A', 0, 2));
            $puedeCalificar = $partido->estadoPartido === 'finalizado';
        @endphp

        <div class="equipo-arbitral-card" id="cal-card-{{ $designacion->idDesignacion }}">
            <div style="display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap">

                {{-- Avatar --}}
                <div class="arbitro-avatar" style="width:52px;height:52px;font-size:1rem;flex-shrink:0">
                    {{ $iniciales }}
                </div>

                {{-- Info árbitro --}}
                <div style="flex:1;min-width:200px">
                    <div style="font-weight:700;font-size:1rem;color:var(--text-primary)">
                        {{ $usuario?->nombreUsuario ?? '—' }}
                    </div>
                    <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:2px">
                        {{ $designacion->rol?->nombre }}
                        @if($arbitro?->categoria)
                            · {{ $arbitro->categoria->nombreCategoria }}
                        @endif
                        · {{ $arbitro?->codigoCarnet }}
                    </div>
                </div>

                {{-- Calificación existente o formulario --}}
                <div style="flex:2;min-width:280px">
                    @if($cal)
                    {{-- Ya calificado --}}
                    <div class="cal-resultado" id="cal-resultado-{{ $designacion->idDesignacion }}">
                        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                            <span class="nota-stars" style="font-size:1.5rem">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor((float)$cal->nota))
                                        ★
                                    @elseif($i - (float)$cal->nota <= 0.5)
                                        ½
                                    @else
                                        ☆
                                    @endif
                                @endfor
                            </span>
                            <span style="font-size:1.25rem;font-weight:800;color:var(--text-primary)">
                                {{ number_format((float)$cal->nota, 1) }}
                            </span>
                            <span class="partido-estado-badge estado-{{ $cal->notaColor === 'green' ? 'confirmado' : ($cal->notaColor === 'blue' ? 'programado' : ($cal->notaColor === 'yellow' ? 'critico' : 'cancelado')) }}">
                                {{ $cal->notaLabel }}
                            </span>
                        </div>
                        <div style="font-size:0.85rem;color:var(--text-secondary);font-style:italic">
                            "{{ $cal->comentario }}"
                        </div>
                        @if($puedeCalificar)
                        <button class="btn btn-ghost btn-sm" style="margin-top:.5rem"
                                onclick="mostrarFormCalificacion({{ $designacion->idDesignacion }})">
                            <i class="fa-solid fa-pen"></i> Modificar
                        </button>
                        @endif
                    </div>
                    @endif

                    @if($puedeCalificar)
                    <div class="cal-form" id="cal-form-{{ $designacion->idDesignacion }}"
                         style="{{ $cal ? 'display:none' : '' }}">

                        {{-- Botones de nota --}}
                        <div style="margin-bottom:.75rem">
                            <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:.5rem;font-weight:600">
                                Nota (1.0 – 5.0)
                            </div>
                            <div class="notas-grid" style="display:flex;flex-wrap:wrap;gap:.4rem">
                                @foreach([1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5] as $n)
                                <button type="button"
                                        class="nota-btn {{ $cal && (float)$cal->nota === (float)$n ? 'selected selected-'.floor($n) : '' }}"
                                        data-nota="{{ $n }}"
                                        data-desig="{{ $designacion->idDesignacion }}"
                                        onclick="seleccionarNota(this)">
                                    {{ number_format($n, 1) }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Comentario --}}
                        <div style="margin-bottom:.75rem">
                            <textarea class="form-input cal-comentario"
                                      id="comentario-{{ $designacion->idDesignacion }}"
                                      maxlength="500"
                                      rows="3"
                                      style="resize:vertical;font-size:.85rem"
                                      placeholder="Comentario obligatorio (mín. 10 caracteres)...">{{ $cal?->comentario }}</textarea>
                            <div style="font-size:.72rem;color:var(--text-muted);text-align:right;margin-top:2px">
                                <span id="cnt-{{ $designacion->idDesignacion }}">0</span>/500
                            </div>
                        </div>

                        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                            <button type="button"
                                    class="btn btn-primary btn-sm"
                                    onclick="calificar({{ $designacion->idDesignacion }})">
                                <i class="fa-solid fa-star"></i>
                                {{ $cal ? 'Actualizar' : 'Calificar' }}
                            </button>
                            @if($cal)
                            <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="cancelarFormCalificacion({{ $designacion->idDesignacion }})">
                                Cancelar
                            </button>
                            @endif
                        </div>
                    </div>
                    @elseif(!$cal)
                    <div style="color:var(--text-muted);font-size:0.85rem">
                        El partido debe estar finalizado para calificar.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="fa-solid fa-users-slash" style="font-size:2rem;color:var(--text-muted);margin-bottom:1rem"></i>
            <p class="empty-state__title">Sin árbitros confirmados</p>
            <p class="empty-state__sub">No hay designaciones confirmadas en este partido.</p>
        </div>
        @endforelse
    </div>

</div>

<script>
window.csrfToken     = "{{ csrf_token() }}";
window.calBaseUrl    = "{{ url('/calificaciones') }}";
window.notasSeleccionadas = {};

function seleccionarNota(btn) {
    const desig = btn.dataset.desig;
    window.notasSeleccionadas[desig] = parseFloat(btn.dataset.nota);
    const btns = btn.closest('.notas-grid').querySelectorAll('.nota-btn');
    btns.forEach(b => {
        b.classList.remove('selected','selected-1','selected-2','selected-3','selected-4','selected-5');
    });
    btn.classList.add('selected', 'selected-' + Math.floor(parseFloat(btn.dataset.nota)));
}

function mostrarFormCalificacion(id) {
    document.getElementById('cal-resultado-' + id).style.display = 'none';
    document.getElementById('cal-form-' + id).style.display = '';
}

function cancelarFormCalificacion(id) {
    document.getElementById('cal-form-' + id).style.display = 'none';
    document.getElementById('cal-resultado-' + id).style.display = '';
}

async function calificar(desigId) {
    const nota      = window.notasSeleccionadas[desigId];
    const comentario= document.getElementById('comentario-' + desigId)?.value?.trim();

    if (!nota) {
        window.novaAlert.error('Selecciona una nota antes de calificar.');
        return;
    }
    if (!comentario || comentario.length < 10) {
        window.novaAlert.error('El comentario debe tener al menos 10 caracteres.');
        return;
    }

    const r = await fetch(window.calBaseUrl + '/' + desigId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ nota, comentario }),
    });

    const data = await r.json();
    if (!data.success) {
        window.novaAlert.error(data.message ?? 'Error al guardar la calificación.');
        return;
    }

    window.novaAlert.success('Calificación guardada. Score del árbitro: ' + data.nuevaScore.toFixed(2));
    setTimeout(() => location.reload(), 1400);
}

// Contador de caracteres
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cal-comentario').forEach(function (ta) {
        const id = ta.id.replace('comentario-', '');
        const cnt = document.getElementById('cnt-' + id);
        if (cnt) {
            ta.addEventListener('input', () => cnt.textContent = ta.value.length);
            cnt.textContent = ta.value.length;
        }
    });
});
</script>
@endsection
