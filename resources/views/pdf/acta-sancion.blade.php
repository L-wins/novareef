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

    /* Estado box */
    .estado-box { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; text-align: center; margin-bottom: 18px; }
    .estado-box .estado-nombre { font-size: 16px; font-weight: 900; color: #1a202c; text-transform: uppercase; }
    .estado-box .estado-meta { font-size: 10px; color: #718096; margin-top: 6px; }

    /* Motivo */
    .motivo-box { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 10.5px; line-height: 1.55; color: #2d3748; }

    /* Historial */
    table.historial { width: 100%; border-collapse: collapse; }
    table.historial th { background: #1a56db; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; }
    table.historial td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.historial tr:nth-child(even) td { background: #f7fafc; }

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
            <p>{{ $sancion->colegio?->nombreColegio ?? 'Colegio de Árbitros' }}</p>
        </div>
        <div class="header-right">
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br>
            por {{ $generadoPor?->nombreUsuario ?? 'Sistema' }}
        </div>
    </div>

    {{-- Título --}}
    <div class="acta-titulo">
        <h2>Resolución Disciplinaria</h2>
        <div class="acta-numero">Sanción #{{ $sancion->idSancion }}</div>
    </div>

    {{-- Estado --}}
    <div class="estado-box">
        <div class="estado-nombre">{{ \App\Models\Sancion::ETIQUETAS_ESTADO[$sancion->estadoSancion][0] ?? $sancion->estadoSancion }}</div>
        <div class="estado-meta">{{ $sancion->tipo?->etiqueta ?? 'Sin tipo' }} — severidad {{ ucfirst($sancion->tipo?->severidad ?? '—') }}</div>
    </div>

    {{-- Árbitro sancionado --}}
    <div class="seccion">
        <div class="seccion-titulo">Árbitro sancionado</div>
        <div class="datos-grid">
            <div class="dato-row">
                <div class="dato-label">Nombre</div>
                <div class="dato-valor">{{ $sancion->arbitro?->usuario?->nombreUsuario ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Documento</div>
                <div class="dato-valor">{{ $sancion->arbitro?->numeroDocumento ?? '—' }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Categoría</div>
                <div class="dato-valor">{{ $sancion->arbitro?->categoria?->nombreCategoria ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Detalle de la sanción --}}
    <div class="seccion">
        <div class="seccion-titulo">Detalle de la sanción</div>
        <div class="datos-grid">
            <div class="dato-row">
                <div class="dato-label">Fecha del hecho</div>
                <div class="dato-valor">{{ $sancion->fechaHecho->format('d/m/Y') }}</div>
            </div>
            <div class="dato-row">
                <div class="dato-label">Suspensión</div>
                <div class="dato-valor">
                    @if ($sancion->tieneSuspension())
                        {{ $sancion->fechaInicioSancion->format('d/m/Y') }}
                        —
                        {{ $sancion->fechaFinSancion?->format('d/m/Y') ?? 'indefinida' }}
                    @else
                        Sin suspensión
                    @endif
                </div>
            </div>
            @if ($sancion->tipo?->articuloReglamento)
                <div class="dato-row">
                    <div class="dato-label">Fundamento reglamentario</div>
                    <div class="dato-valor">{{ $sancion->tipo->articuloReglamento }}</div>
                </div>
            @endif
            @if ($sancion->tieneMultaEconomica && $sancion->movimientoFinanciero)
                <div class="dato-row">
                    <div class="dato-label">Multa económica</div>
                    <div class="dato-valor">
                        ${{ number_format((float) $sancion->movimientoFinanciero->montoTotal, 2) }}
                        — estado: {{ ucfirst($sancion->movimientoFinanciero->estadoMovimiento) }}
                    </div>
                </div>
            @endif
            <div class="dato-row">
                <div class="dato-label">Registrada por</div>
                <div class="dato-valor">{{ $sancion->usuarioImpuso?->nombreUsuario ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Motivo --}}
    <div class="seccion">
        <div class="seccion-titulo">Motivo</div>
        <div class="motivo-box">{{ $sancion->motivoSancion }}</div>
    </div>

    {{-- Historial --}}
    @if ($sancion->historial->isNotEmpty())
    <div class="seccion">
        <div class="seccion-titulo">Historial de actuaciones</div>
        <table class="historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Responsable</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sancion->historial->sortBy('created_at') as $item)
                <tr>
                    <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->tipoAccion)) }}</td>
                    <td>{{ $item->usuarioAccion?->nombreUsuario ?? 'Sistema' }}</td>
                    <td>{{ $item->detalle ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Firma --}}
    <div class="firmas">
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $sancion->usuarioImpuso?->nombreUsuario ?? 'Comité disciplinario' }}</div>
                <div style="font-size:9px;color:#718096;margin-top:2px">Responsable de sanciones</div>
                <div style="font-size:9px;color:#a0aec0">Fecha: {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $sancion->colegio?->nombreColegio ?? '' }}</div>
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
