<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a202c; background: #fff; }
    .page { padding: 2cm 2cm 1.5cm; }

    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a56db; padding-bottom: 12px; margin-bottom: 20px; }
    .header-left h1 { font-size: 18px; font-weight: 800; color: #1a56db; letter-spacing: -0.5px; }
    .header-left p { font-size: 10px; color: #718096; margin-top: 2px; }
    .header-right { text-align: right; font-size: 10px; color: #718096; }

    .titulo { text-align: center; margin: 16px 0 20px; }
    .titulo h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #1a202c; }
    .titulo .rango { font-size: 10px; color: #718096; margin-top: 4px; }

    .totales { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 20px; }
    .total-box { display: table-cell; width: 33%; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; text-align: center; }
    .total-box .label { font-size: 9px; text-transform: uppercase; letter-spacing: .8px; color: #718096; }
    .total-box .valor { font-size: 16px; font-weight: 800; margin-top: 4px; }
    .valor-ingreso { color: #276749; }
    .valor-egreso  { color: #c53030; }
    .total-box .delta { font-size: 9px; color: #718096; margin-top: 3px; }

    .seccion-titulo { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #4a5568; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }

    table.detalle { width: 100%; border-collapse: collapse; }
    table.detalle th { background: #1a56db; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 8px; text-align: left; }
    table.detalle th.num, table.detalle td.num { text-align: right; }
    table.detalle td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.detalle tr:nth-child(even) td { background: #f7fafc; }

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
            Generado el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}<br>
            por {{ $generadoPor?->nombreUsuario ?? 'Sistema' }}
        </div>
    </div>

    <div class="titulo">
        <h2>Reporte Financiero</h2>
        <div class="rango">
            Del {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
            al {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
        </div>
    </div>

    @php $comparativa = $reporte['comparativa']; @endphp
    <div class="totales">
        <div class="total-box">
            <div class="label">Total ingresos</div>
            <div class="valor valor-ingreso">${{ number_format($reporte['totalIngresos'], 0, ',', '.') }}</div>
            <div class="delta">
                Período anterior: ${{ number_format($comparativa['totalIngresos'], 0, ',', '.') }}
                @if ($comparativa['variacionIngresos'] !== null)
                    ({{ $comparativa['variacionIngresos'] >= 0 ? '+' : '' }}{{ number_format($comparativa['variacionIngresos'], 1, ',', '.') }}%)
                @endif
            </div>
        </div>
        <div class="total-box">
            <div class="label">Total egresos</div>
            <div class="valor valor-egreso">${{ number_format($reporte['totalEgresos'], 0, ',', '.') }}</div>
            <div class="delta">
                Período anterior: ${{ number_format($comparativa['totalEgresos'], 0, ',', '.') }}
                @if ($comparativa['variacionEgresos'] !== null)
                    ({{ $comparativa['variacionEgresos'] >= 0 ? '+' : '' }}{{ number_format($comparativa['variacionEgresos'], 1, ',', '.') }}%)
                @endif
            </div>
        </div>
        <div class="total-box">
            <div class="label">Neto</div>
            <div class="valor">${{ number_format($reporte['neto'], 0, ',', '.') }}</div>
            <div class="delta">Período anterior: ${{ number_format($comparativa['neto'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="seccion-titulo">Desglose por categoría</div>

    @if ($reporte['porCategoria']->isEmpty())
        <p>No hay movimientos registrados en este rango de fechas.</p>
    @else
        <table class="detalle">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reporte['porCategoria'] as $fila)
                    <tr>
                        <td>{{ \App\Models\MovimientoFinanciero::ETIQUETAS_CATEGORIA[$fila['categoria']] ?? $fila['categoria'] }}</td>
                        <td>{{ $fila['tipoMovimiento'] === 'ingreso' ? 'Ingreso' : 'Egreso' }}</td>
                        <td class="num">{{ $fila['cantidad'] }}</td>
                        <td class="num {{ $fila['tipoMovimiento'] === 'ingreso' ? 'valor-ingreso' : 'valor-egreso' }}">
                            ${{ number_format($fila['total'], 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Reporte generado automáticamente por NovaReef — Sistema de gestión para colegios de árbitros de fútbol.
    </div>

</div>
</body>
</html>
