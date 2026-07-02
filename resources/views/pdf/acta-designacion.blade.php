<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a202c; background: #fff; }
    .page { padding: 2cm 2cm 1.5cm; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a56db; padding-bottom: 12px; margin-bottom: 20px; }
    .header-left h1 { font-size: 18px; font-weight: 800; color: #1a56db; letter-spacing: -0.5px; }
    .header-left p { font-size: 10px; color: #718096; margin-top: 2px; }
    .header-right { text-align: right; font-size: 10px; color: #718096; }

    /* Título acta */
    .acta-titulo { text-align: center; margin: 16px 0 20px; }
    .acta-titulo h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #1a202c; }
    .acta-titulo .acta-numero { font-size: 10px; color: #718096; margin-top: 4px; }

    /* Sección */
    .seccion { margin-bottom: 18px; }
    .seccion-titulo { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #4a5568; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }

    /* Grid datos */
    .datos-grid { display: table; width: 100%; border-collapse: collapse; }
    .dato-row { display: table-row; }
    .dato-label { display: table-cell; width: 30%; font-weight: 600; color: #4a5568; padding: 3px 6px 3px 0; font-size: 10px; }
    .dato-valor { display: table-cell; color: #1a202c; padding: 3px 0; font-size: 11px; }

    /* Match */
    .match-box { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; text-align: center; margin-bottom: 18px; }
    .match-equipos { font-size: 18px; font-weight: 900; color: #1a202c; }
    .match-vs { color: #a0aec0; font-weight: 400; margin: 0 10px; }
    .match-meta { font-size: 10px; color: #718096; margin-top: 6px; }

    /* Tabla árbitros */
    table.arbitros { width: 100%; border-collapse: collapse; }
    table.arbitros th { background: #1a56db; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; }
    table.arbitros td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.arbitros tr:nth-child(even) td { background: #f7fafc; }
    .estado-confirmada { color: #276749; font-weight: 700; }
    .estado-pendiente  { color: #975a16; }
    .estado-rechazada  { color: #c53030; }

    /* Firma */
    .firmas { display: flex; gap: 40px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
    .firma-box { flex: 1; text-align: center; }
    .firma-linea { border-top: 1px solid #a0aec0; margin-top: 30px; padding-top: 6px; font-size: 9px; color: #718096; }
    .firma-nombre { font-size: 11px; font-weight: 700; color: #1a202c; }

    /* Footer */
    .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <h1>NovaReef</h1>
            <p>{{ $partido->colegio?->nombreColegio ?? 'Colegio de Árbitros' }}</p>
        </div>
        <div class="header-right">
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br>
            por {{ $generadoPor?->nombreUsuario ?? 'Sistema' }}
        </div>
    </div>

    {{-- Título --}}
    <div class="acta-titulo">
        <h2>Acta de Designación Arbitral</h2>
        <div class="acta-numero">Partido #{{ $partido->idPartido }}</div>
    </div>

    {{-- Match --}}
    <div class="match-box">
        <div class="match-equipos">
            {{ $partido->equipoLocal }}
            <span class="match-vs">vs</span>
            {{ $partido->equipoVisitante }}
        </div>
        <div class="match-meta">
            {{ ucfirst($partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
            · {{ $partido->horaPartido }}
        </div>
    </div>

    {{-- Datos del partido --}}
    <div class="seccion">
        <div class="seccion-titulo">Datos del partido</div>
        <div class="datos-grid">
            <div class="dato-row">
                <div class="dato-label">Torneo</div>
                <div class="dato-valor">{{ $partido->torneo?->nombreTorneo ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">División</div>
                <div class="dato-valor">{{ $partido->division?->nombreDivision ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Formato</div>
                <div class="dato-valor">{{ $partido->formato?->nombre ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Sede</div>
                <div class="dato-valor">{{ $partido->sede?->nombreSede ?? '—' }}{{ $partido->sede?->municipio ? ', '.$partido->sede->municipio : '' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Modalidad de pago</div>
                <div class="dato-valor">{{ $partido->modalidadPago ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Estado</div>
                <div class="dato-valor">{{ ucfirst(str_replace('_', ' ', $partido->estadoPartido)) }}</div>
            </div>
        </div>
    </div>

    {{-- Árbitros designados --}}
    <div class="seccion">
        <div class="seccion-titulo">Árbitros designados</div>
        <table class="arbitros">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partido->designaciones->sortBy('rol.orden') as $d)
                <tr>
                    <td>{{ $d->rol?->nombre ?? '—' }}</td>
                    <td>{{ $d->arbitro?->usuario?->nombreUsuario ?? '—' }}</td>
                    <td>{{ $d->arbitro?->numeroDocumento ?? '—' }}</td>
                    <td>{{ $d->arbitro?->categoria?->nombreCategoria ?? '—' }}</td>
                    <td class="estado-{{ $d->estadoDesignacion }}">
                        {{ ucfirst($d->estadoDesignacion) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#a0aec0">Sin árbitros designados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($partido->veedor)
    {{-- Veedor --}}
    <div class="seccion">
        <div class="seccion-titulo">Veedor asignado</div>
        <div class="datos-grid">
            <div class="dato-row">
                <div class="dato-label">Nombre</div>
                <div class="dato-valor">{{ $partido->veedor->nombreUsuario }}</div>
            </div>
        </div>
    </div>
    @endif

    @if($partido->observaciones)
    <div class="seccion">
        <div class="seccion-titulo">Observaciones</div>
        <p style="font-size:10px;color:#4a5568;line-height:1.5">{{ $partido->observaciones }}</p>
    </div>
    @endif

    {{-- Firma --}}
    <div class="firmas">
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $generadoPor?->nombreUsuario ?? 'Designador' }}</div>
                <div style="font-size:9px;color:#718096;margin-top:2px">Responsable de designaciones</div>
                <div style="font-size:9px;color:#a0aec0">Fecha: {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $partido->colegio?->nombreColegio ?? '' }}</div>
                <div style="font-size:9px;color:#718096;margin-top:2px">Colegio de Árbitros</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Documento generado automáticamente por NovaReef — Sistema de Gestión para Colegios de Árbitros de Fútbol
    </div>

</div>
</body>
</html>
