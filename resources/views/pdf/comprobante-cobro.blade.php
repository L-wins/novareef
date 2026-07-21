<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a202c; background: #fff; }
    .page { padding: 2cm 2cm 1.5cm; }

    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #10b981; padding-bottom: 12px; margin-bottom: 20px; }
    .header-left h1 { font-size: 18px; font-weight: 800; color: #10b981; letter-spacing: -0.5px; }
    .header-left p { font-size: 10px; color: #718096; margin-top: 2px; }
    .header-right { text-align: right; font-size: 10px; color: #718096; }

    .titulo { text-align: center; margin: 16px 0 20px; }
    .titulo h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #1a202c; }
    .titulo .lote { font-size: 9px; color: #718096; margin-top: 4px; font-family: DejaVu Sans Mono, monospace; }

    .datos-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .dato-row { display: table-row; }
    .dato-label { display: table-cell; width: 30%; font-weight: 600; color: #4a5568; padding: 3px 6px 3px 0; font-size: 10px; }
    .dato-valor { display: table-cell; color: #1a202c; padding: 3px 0; font-size: 11px; }

    .seccion-titulo { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #4a5568; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin: 16px 0 10px; }

    table.detalle { width: 100%; border-collapse: collapse; }
    table.detalle th { background: #10b981; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; }
    table.detalle th.num, table.detalle td.num { text-align: right; }
    table.detalle td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.detalle tr:nth-child(even) td { background: #f7fafc; }

    .resumen { margin-top: 18px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; }
    .resumen-row { display: table; width: 100%; }
    .resumen-label { display: table-cell; font-size: 10px; color: #4a5568; padding: 3px 0; }
    .resumen-valor { display: table-cell; text-align: right; font-size: 11px; font-weight: 600; padding: 3px 0; }
    .resumen-total .resumen-label, .resumen-total .resumen-valor { font-size: 13px; font-weight: 800; color: #10b981; border-top: 1px solid #e2e8f0; padding-top: 8px; }

    .firmas { display: table; width: 100%; margin-top: 40px; border-spacing: 40px 0; }
    .firma-box { display: table-cell; width: 50%; text-align: center; }
    .firma-linea { border-top: 1px solid #a0aec0; margin-top: 30px; padding-top: 6px; font-size: 9px; color: #718096; }

    .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #a0aec0; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="header-left">
            <h1>NovaReef</h1>
            <p>{{ $colegio?->nombreColegio ?? 'Colegio de Árbitros' }}</p>
        </div>
        <div class="header-right">
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
            @if ($generadoPor)
                <br>por {{ $generadoPor->nombreUsuario }}
            @endif
        </div>
    </div>

    <div class="titulo">
        <h2>Recibo de pago</h2>
        <div class="lote">Lote {{ $idLotePago }}</div>
    </div>

    <div class="datos-grid">
        <div class="dato-row">
            <span class="dato-label">Árbitro</span>
            <span class="dato-valor">{{ $datos['arbitro']->usuario->nombreUsuario ?? 'Árbitro #' . $datos['arbitro']->idArbitro }}</span>
        </div>
        @if ($datos['arbitro']->numeroDocumento)
        <div class="dato-row">
            <span class="dato-label">Documento</span>
            <span class="dato-valor">{{ $datos['arbitro']->numeroDocumento }}</span>
        </div>
        @endif
        <div class="dato-row">
            <span class="dato-label">Fecha del pago</span>
            <span class="dato-valor">{{ $datos['fecha']->format('d/m/Y') }}</span>
        </div>
        <div class="dato-row">
            <span class="dato-label">Método de pago</span>
            <span class="dato-valor">{{ ucfirst(str_replace('_', ' ', $datos['metodoPago'])) }}</span>
        </div>
    </div>

    <div class="seccion-titulo">Conceptos pagados</div>
    <table class="detalle">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($datos['conceptos'] as $fila)
                <tr>
                    <td>{{ $fila['concepto'] }}</td>
                    <td class="num">${{ number_format($fila['monto'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="resumen">
        <div class="resumen-row resumen-total">
            <span class="resumen-label">Total pagado</span>
            <span class="resumen-valor">${{ number_format($datos['total'], 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="firmas">
        <div class="firma-box">
            <div class="firma-linea">Recibe — Tesorería</div>
        </div>
        <div class="firma-box">
            <div class="firma-linea">Paga — Árbitro</div>
        </div>
    </div>

    <div class="footer">
        Comprobante generado automáticamente por NovaReef. Lote {{ $idLotePago }}.
    </div>

</div>
</body>
</html>
