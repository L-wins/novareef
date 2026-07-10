<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a202c; background: #fff; }
    .page { padding: 1.5cm 1.5cm 1cm; }

    /* Header */
    .header { border-bottom: 3px solid #1a56db; padding-bottom: 12px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: 800; color: #1a56db; letter-spacing: -0.5px; }
    .header p { font-size: 10px; color: #718096; margin-top: 2px; }

    .titulo { text-align: center; margin: 12px 0 16px; }
    .titulo h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #1a202c; }
    .titulo .sub { font-size: 10px; color: #718096; margin-top: 4px; }

    /* Stats */
    .stats-box { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; }
    .stats-titulo { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #4a5568; margin-bottom: 8px; }
    table.stats { width: 100%; border-collapse: collapse; }
    table.stats td { padding: 3px 8px 3px 0; font-size: 10px; }
    table.stats td.num { font-weight: 800; font-size: 13px; color: #1a56db; width: 40px; text-align: right; padding-right: 10px; }

    /* Tabla historial */
    table.historial { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.historial th { background: #1a56db; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 7px; text-align: left; }
    table.historial td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
    table.historial tr:nth-child(even) td { background: #f7fafc; }

    .estado-confirmada { color: #276749; font-weight: 700; }
    .estado-pendiente  { color: #975a16; }
    .estado-rechazada  { color: #c53030; }

    .footer { margin-top: 18px; text-align: center; font-size: 8px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <h1>NovaReef</h1>
        <p>{{ $arbitro->colegio?->nombreColegio ?? 'Colegio de Árbitros' }}</p>
    </div>

    {{-- Título --}}
    <div class="titulo">
        <h2>Historial de Partidos</h2>
        <div class="sub">
            {{ $arbitro->usuario?->nombreUsuario ?? 'Árbitro' }}
            · Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>
    </div>

    {{-- Estadísticas de carrera --}}
    <div class="stats-box">
        <div class="stats-titulo">Estadísticas de carrera</div>
        <table class="stats">
            <tr>
                <td class="num">{{ $stats['totalDirigidos'] }}</td>
                <td>Partidos dirigidos</td>
                <td class="num">{{ $stats['torneos'] }}</td>
                <td>Torneos</td>
                <td class="num">{{ $stats['rechazadas'] }}</td>
                <td>Designaciones rechazadas</td>
            </tr>
            @if($stats['porRol']->isNotEmpty())
            <tr>
                @foreach($stats['porRol'] as $rol => $total)
                <td class="num">{{ $total }}</td>
                <td>Como {{ $rol }}</td>
                @endforeach
            </tr>
            @endif
        </table>
    </div>

    {{-- Tabla --}}
    <table class="historial">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Partido</th>
                <th>Torneo</th>
                <th>Sede</th>
                <th>Rol</th>
                <th>Pago</th>
                <th>Mi estado</th>
                <th>Estado partido</th>
            </tr>
        </thead>
        <tbody>
            @forelse($historial as $desig)
            @php
                $partido = $desig->partido;
                $pago    = $desig->pago ?? ['valor' => null, 'modalidad' => null];
            @endphp
            <tr>
                <td>{{ $partido->fechaPartido?->format('d/m/Y') }}</td>
                <td>{{ $partido->equipoLocal }} vs {{ $partido->equipoVisitante }}</td>
                <td>{{ $partido->torneo?->nombreTorneo ?? '—' }}</td>
                <td>{{ $partido->sede?->nombreSede ?? '—' }}</td>
                <td>{{ $desig->rol?->nombre ?? '—' }}</td>
                <td>
                    @if(($pago['modalidad'] ?? null) === 'nomina')
                        Nómina
                    @elseif($pago['valor'] !== null)
                        ${{ number_format($pago['valor'], 0, ',', '.') }} COP
                    @else
                        —
                    @endif
                </td>
                <td class="estado-{{ $desig->estadoDesignacion }}">{{ ucfirst($desig->estadoDesignacion) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $partido->estadoPartido)) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:#a0aec0;padding:14px">Sin partidos en el período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        NovaReef — Sistema de gestión para colegios de árbitros · {{ $historial->count() }} registro{{ $historial->count() !== 1 ? 's' : '' }}
    </div>

</div>
</body>
</html>
