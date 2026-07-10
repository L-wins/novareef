{{-- Gráfico de barras agrupadas: ingresos vs egresos por mes.
     SVG generado server-side (sin librerías JS); colores y hover en
     css/finanzas/chart.css (paleta validada por tema con el skill dataviz).
     Espera: $serie = Collection de ['mes','etiqueta','ingresos','egresos']. --}}
@php
    // ── Geometría ─────────────────────────
    $margenIzq = 52;   $margenDer = 8;
    $margenSup = 10;   $margenInf = 26;
    $altoPlot  = 170;
    $anchoBarra = 20;  $gapBarras = 2;    // 2px de "surface gap" entre las dos barras del mes
    $padGrupo   = 26;

    $n          = $serie->count();
    $anchoGrupo = ($anchoBarra * 2) + $gapBarras;
    $anchoPlot  = $n * ($anchoGrupo + $padGrupo);
    $anchoSvg   = $margenIzq + $anchoPlot + $margenDer;
    $altoSvg    = $margenSup + $altoPlot + $margenInf;

    // ── Escala Y: techo "bonito" (1/2/5 × 10^k) sobre el máximo ──
    $maximo = max(1.0, (float) $serie->max(fn ($m) => max($m['ingresos'], $m['egresos'])));
    $pot    = 10 ** floor(log10($maximo));
    $techo  = collect([1, 2, 2.5, 5, 10])->first(fn ($f) => $f * $pot >= $maximo) * $pot;

    $escalaY = fn (float $v): float => $altoPlot * $v / $techo;

    // Etiqueta compacta del eje: $1,2M · $850K · $900
    $abreviar = function (float $v): string {
        if ($v >= 1_000_000) return '$' . rtrim(rtrim(number_format($v / 1_000_000, 1, ',', '.'), '0'), ',') . 'M';
        if ($v >= 1_000)     return '$' . rtrim(rtrim(number_format($v / 1_000, 1, ',', '.'), '0'), ',') . 'K';
        return '$' . number_format($v, 0, ',', '.');
    };

    // Barra con solo el extremo superior redondeado (data-end), anclada a la base
    $barra = function (float $x, float $h, float $w) use ($margenSup, $altoPlot): string {
        if ($h <= 0) return '';
        $r    = min(3.0, $h, $w / 2);
        $y    = $margenSup + $altoPlot - $h;
        $base = $margenSup + $altoPlot;

        return sprintf(
            'M%.1f %.1f L%.1f %.1f Q%.1f %.1f %.1f %.1f L%.1f %.1f Q%.1f %.1f %.1f %.1f L%.1f %.1f Z',
            $x, $base,
            $x, $y + $r,
            $x, $y, $x + $r, $y,
            $x + $w - $r, $y,
            $x + $w, $y, $x + $w, $y + $r,
            $x + $w, $base,
        );
    };

    $cop = fn (float $v): string => '$' . number_format($v, 0, ',', '.');
@endphp

<div class="fin-chart-card">
    <div class="fin-chart-card__head">
        <div>
            <p class="fin-chart-card__title">Tendencia mensual</p>
            <span class="fin-chart-card__sub">Montos registrados por mes (excluye anulados)</span>
        </div>
        <div class="fin-chart-legend" aria-hidden="true">
            <span><i class="swatch-ingreso"></i> Ingresos</span>
            <span><i class="swatch-egreso"></i> Egresos</span>
        </div>
    </div>

    <div class="fin-chart-scroll">
        <div class="fin-chart">
            <svg viewBox="0 0 {{ $anchoSvg }} {{ $altoSvg }}" role="img"
                 aria-label="Barras de ingresos y egresos por mes">
                {{-- Grid horizontal recesivo + etiquetas del eje Y --}}
                @foreach ([0, 0.25, 0.5, 0.75, 1] as $frac)
                    @php $y = $margenSup + $altoPlot - ($altoPlot * $frac); @endphp
                    <line class="grid-line" x1="{{ $margenIzq }}" x2="{{ $anchoSvg - $margenDer }}" y1="{{ $y }}" y2="{{ $y }}"/>
                    <text class="eje-label" x="{{ $margenIzq - 6 }}" y="{{ $y + 3 }}" text-anchor="end">{{ $abreviar($techo * $frac) }}</text>
                @endforeach

                {{-- Barras por mes --}}
                @foreach ($serie as $i => $mes)
                    @php $x = $margenIzq + ($padGrupo / 2) + $i * ($anchoGrupo + $padGrupo); @endphp
                    <g class="mes-grupo">
                        <title>{{ $mes['etiqueta'] }} — Ingresos: {{ $cop($mes['ingresos']) }} · Egresos: {{ $cop($mes['egresos']) }}</title>
                        @if ($mes['ingresos'] > 0)
                            <path class="barra-ingreso" d="{{ $barra($x, $escalaY($mes['ingresos']), $anchoBarra) }}"/>
                        @endif
                        @if ($mes['egresos'] > 0)
                            <path class="barra-egreso" d="{{ $barra($x + $anchoBarra + $gapBarras, $escalaY($mes['egresos']), $anchoBarra) }}"/>
                        @endif
                        {{-- Zona de hover del mes completo (target mayor que la marca) --}}
                        <rect x="{{ $x - $padGrupo / 2 }}" y="{{ $margenSup }}" width="{{ $anchoGrupo + $padGrupo }}" height="{{ $altoPlot }}" fill="transparent"/>
                        <text class="eje-label" x="{{ $x + $anchoGrupo / 2 }}" y="{{ $margenSup + $altoPlot + 16 }}" text-anchor="middle">{{ $mes['etiqueta'] }}</text>
                    </g>
                @endforeach

                {{-- Línea base --}}
                <line class="grid-line" x1="{{ $margenIzq }}" x2="{{ $anchoSvg - $margenDer }}"
                      y1="{{ $margenSup + $altoPlot }}" y2="{{ $margenSup + $altoPlot }}"/>
            </svg>
        </div>
    </div>

    {{-- Vista de tabla del gráfico (accesibilidad / lectores de pantalla) --}}
    <details class="fin-chart-tabla">
        <summary class="fin-chart-card__sub">Ver datos del gráfico en tabla</summary>
        <table class="data-table">
            <thead>
                <tr><th>Mes</th><th class="text-right">Ingresos</th><th class="text-right">Egresos</th></tr>
            </thead>
            <tbody>
                @foreach ($serie as $mes)
                    <tr>
                        <td>{{ $mes['etiqueta'] }}</td>
                        <td class="text-right">{{ $cop($mes['ingresos']) }}</td>
                        <td class="text-right">{{ $cop($mes['egresos']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
</div>
