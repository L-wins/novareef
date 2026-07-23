@php
    $etiquetasEstado = [
        'borrador'   => 'Borrador',
        'programado' => 'Programado',
        'confirmado' => 'Confirmado',
        'critico'    => 'Crítico',
        'aplazado'   => 'Aplazado',
        'cancelado'  => 'Cancelado',
        'finalizado' => 'Finalizado',
    ];
    $totalPartidos = $resumen['partidosPorEstado']->sum();
@endphp
<div class="est-tiles">
    <div class="est-tile">
        <div class="est-tile__value">{{ $resumen['arbitrosActivos'] }}</div>
        <div class="est-tile__label">Árbitros activos</div>
    </div>
    <div class="est-tile">
        <div class="est-tile__value">{{ $resumen['totalCategorias'] }}</div>
        <div class="est-tile__label">Categorías</div>
    </div>
    <div class="est-tile">
        <div class="est-tile__value">{{ $totalPartidos }}</div>
        <div class="est-tile__label">Partidos totales</div>
    </div>
    <div class="est-tile est-tile--estados">
        <div class="est-tile__label">Partidos por estado</div>
        <div class="est-tile__estados-list">
            @forelse ($resumen['partidosPorEstado'] as $estado => $cantidad)
                <span class="est-estado-chip"><strong>{{ $cantidad }}</strong> {{ $etiquetasEstado[$estado] ?? $estado }}</span>
            @empty
                <span class="est-inline-muted">Sin partidos registrados.</span>
            @endforelse
        </div>
    </div>
</div>
