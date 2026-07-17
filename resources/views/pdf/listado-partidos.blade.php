<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a202c; background: #fff; }
    .page { padding: 1.5cm 1.5cm 1.2cm; }

    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a56db; padding-bottom: 10px; margin-bottom: 14px; }
    .header-left h1 { font-size: 16px; font-weight: 800; color: #1a56db; letter-spacing: -0.5px; }
    .header-left p { font-size: 9px; color: #718096; margin-top: 2px; }
    .header-right { text-align: right; font-size: 9px; color: #718096; }

    .listado-titulo { text-align: center; margin: 10px 0 16px; }
    .listado-titulo h2 { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #1a202c; }
    .listado-titulo .listado-sub { font-size: 9px; color: #718096; margin-top: 3px; }

    .bloque-partido { margin-bottom: 14px; page-break-inside: avoid; }

    .contexto { color: #c00; font-weight: 700; font-size: 10px; margin-bottom: 3px; }
    .contexto span { margin-right: 18px; }

    table.partido-tabla { width: 100%; border-collapse: collapse; border: 2px solid #000; }
    table.partido-tabla td { border: 1px solid #000; padding: 4px 6px; font-size: 9.5px; vertical-align: middle; }
    table.partido-tabla td.label { font-weight: 700; background: #f0f0f0; width: 11%; }
    table.partido-tabla td.valor { width: 26%; }
    table.partido-tabla td.label-rol { font-weight: 700; background: #f0f0f0; width: 11%; }
    table.partido-tabla td.valor-rol { width: 26%; }

    .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="header-left">
            <h1>NovaReef</h1>
            <p>{{ $torneo->colegio?->nombreColegio ?? 'Colegio de Árbitros' }}</p>
        </div>
        <div class="header-right">
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br>
            por {{ $generadoPor?->nombreUsuario ?? 'Sistema' }}
        </div>
    </div>

    <div class="listado-titulo">
        <h2>Listado de designaciones</h2>
        <div class="listado-sub">{{ $torneo->nombreTorneo }} — {{ $partidos->count() }} partido{{ $partidos->count() === 1 ? '' : 's' }}</div>
    </div>

    @forelse($partidos as $partido)
    @php
        $porRolSlot = $partido->slots->keyBy(fn ($s) => ($s->rol?->nombre ?? '') . '#' . $s->numeroSlot);
        $nombreArbitro = fn (string $rol, int $slot = 1) => $porRolSlot->get("{$rol}#{$slot}")?->designacion?->arbitro?->usuario?->nombreUsuario ?: '—';
    @endphp
    <div class="bloque-partido">
        <div class="contexto">
            @if($partido->observaciones)
            <span>{{ $partido->observaciones }}</span>
            @endif
            <span>{{ $partido->division?->nombreDivision ?? '—' }}</span>
            <span>{{ $partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM') }}</span>
        </div>

        <table class="partido-tabla">
            <tr>
                <td class="label">PARTIDO</td>
                <td class="valor">{{ $partido->equipoLocal }}</td>
                <td class="valor" style="border-right:2px solid #000;">{{ $partido->equipoVisitante }}</td>
                <td class="label-rol">ARBITRO</td>
                <td class="valor-rol">{{ $nombreArbitro('Central') }}</td>
            </tr>
            <tr>
                <td class="label">ESTADIO</td>
                <td class="valor" colspan="2" style="border-right:2px solid #000;">{{ $partido->sede?->nombreSede ?? 'Sin sede asignada' }}</td>
                <td class="label-rol">LINEA UNO</td>
                <td class="valor-rol">{{ $nombreArbitro('Asistente', 1) }}</td>
            </tr>
            <tr>
                <td class="label">DIA</td>
                <td class="valor">{{ $partido->fechaPartido?->locale('es')->isoFormat('dddd D [de] MMMM') }}</td>
                <td class="valor" style="border-right:2px solid #000;">{{ substr((string) $partido->horaPartido, 0, 5) }}</td>
                <td class="label-rol">LINEA DOS</td>
                <td class="valor-rol">{{ $nombreArbitro('Asistente', 2) }}</td>
            </tr>
            <tr>
                <td class="label">CIUDAD</td>
                <td class="valor" colspan="2" style="border-right:2px solid #000;">{{ $partido->sede?->municipio ?? '—' }}</td>
                <td class="label-rol">EMERGENTE</td>
                <td class="valor-rol">{{ $nombreArbitro('Cuarto') }}</td>
            </tr>
        </table>
    </div>
    @empty
    <p style="text-align:center;color:#a0aec0;margin-top:20px;">No hay partidos que coincidan con los filtros.</p>
    @endforelse

    <div class="footer">
        Documento generado automáticamente por NovaReef — Sistema de Gestión para Colegios de Árbitros de Fútbol
    </div>

</div>
</body>
</html>
