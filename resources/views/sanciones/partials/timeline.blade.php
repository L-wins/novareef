@php
    $etiquetasAccion = [
        'impuesta' => 'Sanción impuesta',
        'cumplida' => 'Sanción cumplida',
        'anulada' => 'Sanción anulada',
        'apelada' => 'Apelación presentada',
        'apelacion_resuelta' => 'Apelación resuelta',
    ];
@endphp

@if ($sancion->historial->isEmpty())
    <p class="san-timeline-empty">Sin movimientos registrados.</p>
@else
    <div class="san-timeline">
        @foreach ($sancion->historial as $item)
            <div class="san-timeline__item">
                <span class="san-timeline__dot" data-color="{{ \App\Models\HistorialSancion::colorPorTipo($item->tipoAccion) }}"></span>
                <div class="san-timeline__head">
                    <span class="san-timeline__label">{{ $etiquetasAccion[$item->tipoAccion] ?? ucfirst(str_replace('_', ' ', $item->tipoAccion)) }}</span>
                    <span class="san-timeline__meta">
                        {{ $item->created_at->format('d/m/Y H:i') }}
                        @if ($item->usuarioAccion) · {{ $item->usuarioAccion->nombreUsuario }} @endif
                    </span>
                </div>
                @if ($item->detalle)
                    <p class="san-timeline__detalle">{{ $item->detalle }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
