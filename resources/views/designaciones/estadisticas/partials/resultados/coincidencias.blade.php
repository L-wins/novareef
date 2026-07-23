@php
    $seleccionados = $coincidencias['arbitros'];
    $partidos      = $coincidencias['partidos'];
    $roles         = $coincidencias['roles'];
    $pares         = $coincidencias['pares'];
@endphp

@if ($seleccionados->count() < 2)
    <p class="est-inline-muted">Elige al menos 2 árbitros arriba para ver cuántos partidos han compartido.</p>
@else
    <div class="est-chips-row">
        @foreach ($seleccionados as $arb)
            <span class="est-rol-chip">{{ $arb->usuario?->nombreUsuario ?? '—' }}</span>
        @endforeach
    </div>

    @if ($seleccionados->count() === 2)
        {{-- Con exactamente 2, el desglose por pares y "todos juntos" son la
             misma cifra — mostrar solo el número grande, sin redundancia. --}}
        <div class="est-coincidencias-count">{{ $partidos->count() }}</div>
        <p class="est-coincidencias-caption">partidos compartidos (designaciones no rechazadas)</p>
    @else
        {{-- Con 3+ seleccionados, el conteo "todos a la vez" suele ser bajo o
             cero — el desglose por pares es lo realmente accionable: cuántas
             veces coincidió cada dos de ellos, sin exigir al resto. --}}
        <h3 class="est-subtitle">Coincidencias por pareja</h3>
        @if ($pares->isEmpty())
            <p class="est-inline-muted">Ninguno de los seleccionados ha compartido partido con otro.</p>
        @else
            <div class="disp-table-wrap est-pares-table">
                <table class="disp-table">
                    <thead>
                        <tr>
                            <th>Árbitro A</th>
                            <th>Árbitro B</th>
                            <th>Partidos juntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pares as $par)
                            <tr>
                                <td>{{ $par['a']?->usuario?->nombreUsuario ?? '—' }}</td>
                                <td>{{ $par['b']?->usuario?->nombreUsuario ?? '—' }}</td>
                                <td><strong>{{ $par['total'] }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h3 class="est-subtitle">Los {{ $seleccionados->count() }} juntos a la vez</h3>
        <div class="est-coincidencias-count">{{ $partidos->count() }}</div>
        <p class="est-coincidencias-caption">partidos donde coincidieron todos los seleccionados</p>
    @endif

    @if ($partidos->isNotEmpty())
        <div>
            @foreach ($partidos as $partido)
                <div class="est-partido-row">
                    <div>
                        <div class="est-partido-row__equipos">{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</div>
                        <div class="est-partido-row__meta">
                            {{ $partido->torneo?->nombreTorneo ?? '—' }} · {{ $partido->fechaPartido?->translatedFormat('d/m/Y') }}
                        </div>
                    </div>
                    <div class="est-rol-chips">
                        @foreach ($seleccionados as $arb)
                            @if (isset($roles[$partido->idPartido][$arb->idArbitro]))
                                <span class="est-rol-chip">
                                    {{ $arb->usuario?->nombreUsuario }}: {{ $roles[$partido->idPartido][$arb->idArbitro] }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif
