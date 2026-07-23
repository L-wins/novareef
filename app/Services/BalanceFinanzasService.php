<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbonoMovimiento;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use App\Models\Partido;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reportes por rango de fechas, balance general del colegio y los agregados
 * que se derivan de él (mora, bolsillos, saldo por método de pago). Extraído
 * de ReporteFinanzasService (que superaba las ~600-700 líneas documentadas en
 * CLAUDE.md) — ese servicio se quedó con el listado filtrado y los
 * movimientos institucionales; el estado de cuenta/comprobantes por árbitro
 * vive en EstadoCuentaArbitroService. Mismo patrón que ya usa Designaciones
 * (DesignacionService / CandidatosDesignacionService / ReporteDesignacionesService).
 */
final class BalanceFinanzasService
{
    private const MESES_CORTOS = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Reporte para un rango de fechas libre, sobre el monto registrado de los
     * movimientos no anulados (base contable, no solo lo efectivamente
     * cobrado/pagado). Incluye la comparativa contra el período inmediatamente
     * anterior de igual duración (variaciones en % — null si no hay base).
     *
     * @return array{
     *     totalIngresos: float, totalEgresos: float, neto: float,
     *     porCategoria: Collection<int, array{categoria: string, tipoMovimiento: string, total: float, cantidad: int}>,
     *     comparativa: array{desde: string, hasta: string, totalIngresos: float, totalEgresos: float, neto: float,
     *         variacionIngresos: ?float, variacionEgresos: ?float},
     * }
     */
    public function reporte(int $idColegio, string $desde, string $hasta): array
    {
        $movimientos = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            // Saldo inicial/ajuste de caja no es desempeño operativo del
            // período — inflaría el mes en que se registró.
            ->where('categoria', '!=', MovimientoFinanciero::CATEGORIA_SALDO_INICIAL)
            ->whereBetween('fechaMovimiento', [$desde, $hasta])
            ->get();

        $totalIngresos = $movimientos->where('tipoMovimiento', MovimientoFinanciero::TIPO_INGRESO)->sum(fn ($m) => (float) $m->montoTotal);
        $totalEgresos  = $movimientos->where('tipoMovimiento', MovimientoFinanciero::TIPO_EGRESO)->sum(fn ($m) => (float) $m->montoTotal);

        $porCategoria = $movimientos
            ->groupBy('categoria')
            ->map(function (Collection $grupo, string $categoria) {
                return [
                    'categoria'      => $categoria,
                    'tipoMovimiento' => $grupo->first()->tipoMovimiento,
                    'total'          => $grupo->sum(fn ($m) => (float) $m->montoTotal),
                    'cantidad'       => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return [
            'totalIngresos' => $totalIngresos,
            'totalEgresos'  => $totalEgresos,
            'neto'          => $totalIngresos - $totalEgresos,
            'porCategoria'  => $porCategoria,
            'comparativa'   => $this->comparativaPeriodoAnterior($idColegio, $desde, $hasta, $totalIngresos, $totalEgresos),
        ];
    }

    /**
     * Serie mensual de ingresos vs egresos (movimientos no anulados) entre dos
     * fechas, agregada en SQL y con los meses sin movimientos rellenados en
     * cero — lista para pintar el gráfico de barras del reporte.
     *
     * @return Collection<int, array{mes: string, etiqueta: string, ingresos: float, egresos: float}>
     */
    public function serieMensual(int $idColegio, string $desde, string $hasta): Collection
    {
        $filas = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->where('categoria', '!=', MovimientoFinanciero::CATEGORIA_SALDO_INICIAL)
            ->whereBetween('fechaMovimiento', [$desde, $hasta])
            ->selectRaw("DATE_FORMAT(fechaMovimiento, '%Y-%m') as mes, tipoMovimiento, COALESCE(SUM(montoTotal), 0) as total")
            ->groupBy('mes', 'tipoMovimiento')
            ->get()
            ->groupBy('mes');

        $periodo = CarbonPeriod::create(
            Carbon::parse($desde)->startOfMonth(),
            '1 month',
            Carbon::parse($hasta)->startOfMonth(),
        );

        return collect($periodo)->map(function (Carbon $mes) use ($filas): array {
            $delMes = $filas->get($mes->format('Y-m'), collect());

            return [
                'mes'      => $mes->format('Y-m'),
                'etiqueta' => self::MESES_CORTOS[$mes->month] . ' ' . $mes->format('y'),
                'ingresos' => (float) $delMes->firstWhere('tipoMovimiento', MovimientoFinanciero::TIPO_INGRESO)?->total,
                'egresos'  => (float) $delMes->firstWhere('tipoMovimiento', MovimientoFinanciero::TIPO_EGRESO)?->total,
            ];
        })->values();
    }

    /**
     * Balance general del colegio: saldo en caja (dinero real que ha
     * entrado/salido — excluye abonos de compensación, que son solo un ajuste
     * contable interno sin movimiento de efectivo) y, por cada árbitro con
     * saldo activo, cuánto le debe el colegio (nómina/externo pendiente) y
     * cuánto debe el árbitro al colegio (mensualidad/multa pendiente).
     *
     * Los saldos por árbitro se agregan en UNA query (GROUP BY idArbitro con
     * el abonado resuelto vía LEFT JOIN) — la versión anterior hacía dos
     * queries por árbitro del colegio.
     *
     * @return array{
     *     saldoEnCaja: float, totalLeDebemos: float, totalNosDeben: float,
     *     porArbitro: Collection<int, array{arbitro: Arbitro, leDebemos: float, nosDebe: float}>,
     * }
     */
    public function balanceGeneral(int $idColegio): array
    {
        $abonosIngreso = $this->totalAbonosReales($idColegio, MovimientoFinanciero::TIPO_INGRESO);
        $abonosEgreso  = $this->totalAbonosReales($idColegio, MovimientoFinanciero::TIPO_EGRESO);

        $agregados = MovimientoFinanciero::where('movimientos_financieros.idColegio', $idColegio)
            ->whereNotNull('idArbitro')
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->whereIn('categoria', [
                MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
                MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO,
                MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
                MovimientoFinanciero::CATEGORIA_MULTA,
            ])
            ->leftJoinSub($this->subqueryAbonado(), 'ab', 'ab.idMovimiento', '=', 'movimientos_financieros.idMovimiento')
            ->selectRaw("
                idArbitro,
                COALESCE(SUM(CASE WHEN categoria IN ('nomina_arbitro', 'arbitro_externo') THEN montoTotal - COALESCE(ab.abonado, 0) ELSE 0 END), 0) as leDebemos,
                COALESCE(SUM(CASE WHEN categoria IN ('mensualidad', 'multa') THEN montoTotal - COALESCE(ab.abonado, 0) ELSE 0 END), 0) as nosDebe,
                MIN(CASE WHEN categoria IN ('mensualidad', 'multa') AND (montoTotal - COALESCE(ab.abonado, 0)) > 0 THEN fechaMovimiento END) as fechaDeudaMasAntigua
            ")
            ->groupBy('idArbitro')
            ->get()
            ->keyBy('idArbitro');

        // Todos los árbitros del colegio, no solo los que tienen saldo
        // pendiente — la fila es también el punto de entrada a su ficha
        // financiera, así que debe poder accederse aunque esté en ceros.
        // Arbitro::where(idColegio) ya excluye archivados (soft delete).
        $porArbitro = Arbitro::where('idColegio', $idColegio)
            ->with('usuario')
            ->get()
            ->map(fn (Arbitro $arbitro) => [
                'arbitro'              => $arbitro,
                'leDebemos'            => (float) ($agregados[$arbitro->idArbitro]->leDebemos ?? 0),
                'nosDebe'              => (float) ($agregados[$arbitro->idArbitro]->nosDebe ?? 0),
                'fechaDeudaMasAntigua' => $agregados[$arbitro->idArbitro]->fechaDeudaMasAntigua ?? null,
            ])
            ->sortByDesc('leDebemos')
            ->values();

        return [
            'saldoEnCaja'    => $abonosIngreso - $abonosEgreso,
            'totalLeDebemos' => $porArbitro->sum('leDebemos'),
            'totalNosDeben'  => $porArbitro->sum('nosDebe'),
            'porArbitro'     => $porArbitro,
        ];
    }

    /**
     * Filtra el balance a solo los árbitros en mora (nosDebe > 0), con
     * antigüedad aproximada contada desde fechaMovimiento del cargo más
     * viejo aún pendiente — mismo criterio que usa un AR Aging Report
     * (QuickBooks/Xero) cuando el cliente no tiene términos de crédito
     * configurados: se cuenta desde la emisión, no desde un vencimiento
     * explícito (este colegio no tiene ese campo hoy).
     *
     * @param  array{porArbitro: Collection<int, array{arbitro: Arbitro, nosDebe: float, fechaDeudaMasAntigua: ?string}>}  $balance  Resultado de balanceGeneral().
     * @return Collection<int, array{arbitro: Arbitro, nosDebe: float, diasMora: int, bucket: string}>
     */
    public function moraDesdeBalance(array $balance): Collection
    {
        return $balance['porArbitro']
            ->filter(fn (array $f) => $f['nosDebe'] > 0.0)
            ->map(function (array $f): array {
                $diasMora = $f['fechaDeudaMasAntigua'] !== null
                    ? (int) Carbon::parse($f['fechaDeudaMasAntigua'])->diffInDays(today())
                    : 0;

                return [
                    'arbitro'  => $f['arbitro'],
                    'nosDebe'  => $f['nosDebe'],
                    'diasMora' => $diasMora,
                    'bucket'   => match (true) {
                        $diasMora <= 30 => '0-30',
                        $diasMora <= 60 => '31-60',
                        $diasMora <= 90 => '61-90',
                        default         => '90+',
                    },
                ];
            })
            ->sortByDesc('diasMora')
            ->values();
    }

    /**
     * Desglosa saldoEnCaja por método de pago (efectivo vs. pago_digital) —
     * el equivalente simple a "seguimiento de dónde entra/sale el dinero"
     * sin necesitar una tabla de cuentas bancarias: cada abono ya registra
     * si fue efectivo o digital, así que basta con agrupar por esa columna.
     * Excluye `nomina` (compensación interna, no es dinero real).
     *
     * @return array<string, float>  Ej. ['efectivo' => 120000.0, 'pago_digital' => 45000.0]
     */
    public function saldoPorMetodoPago(int $idColegio): array
    {
        $filas = AbonoMovimiento::query()
            ->join('movimientos_financieros', 'movimientos_financieros.idMovimiento', '=', 'abonos_movimiento.idMovimiento')
            ->where('movimientos_financieros.idColegio', $idColegio)
            ->where('abonos_movimiento.anulado', false)
            ->whereIn('abonos_movimiento.metodoPago', AbonoMovimiento::METODOS_MANUALES)
            ->selectRaw("
                abonos_movimiento.metodoPago as metodoPago,
                SUM(CASE WHEN tipoMovimiento = 'ingreso' THEN abonos_movimiento.monto ELSE -abonos_movimiento.monto END) as saldo
            ")
            ->groupBy('abonos_movimiento.metodoPago')
            ->pluck('saldo', 'metodoPago');

        return collect(AbonoMovimiento::METODOS_MANUALES)
            ->mapWithKeys(fn (string $metodo) => [$metodo => (float) ($filas[$metodo] ?? 0)])
            ->all();
    }

    /**
     * "Bolsillos" del colegio — a partir de balanceGeneral() separa la caja
     * bruta de lo realmente disponible: `disponibleReal` es cuánto del saldo
     * en caja NO está ya comprometido con nómina pendiente de árbitros. Las
     * deudas de árbitros hacia el colegio no cuentan porque todavía no son
     * caja.
     *
     * @return array{saldoEnCaja: float, disponibleReal: float, pendientePorCobrar: float, pendientePorPagar: float}
     */
    public function bolsillos(int $idColegio): array
    {
        return $this->bolsillosDesdeBalance($this->balanceGeneral($idColegio));
    }

    /**
     * Igual que bolsillos(), pero a partir de un balanceGeneral() que el
     * caller ya calculó — evita repetir el agregado cuando ambos hacen falta
     * a la vez (ej. DashboardService::paraTesorero()).
     *
     * @param  array{saldoEnCaja: float, totalLeDebemos: float, totalNosDeben: float, porArbitro: Collection}  $balance
     * @return array{saldoEnCaja: float, disponibleReal: float, pendientePorCobrar: float, pendientePorPagar: float}
     */
    public function bolsillosDesdeBalance(array $balance): array
    {
        return [
            'saldoEnCaja'        => $balance['saldoEnCaja'],
            'disponibleReal'     => $balance['saldoEnCaja'] - $balance['totalLeDebemos'],
            'pendientePorCobrar' => $balance['totalNosDeben'],
            'pendientePorPagar'  => $balance['totalLeDebemos'],
        ];
    }

    /**
     * Partidos finalizados con modalidad "nómina" que no generaron ningún
     * egreso de nómina — señal de que el sistema se quedó sin poder pagarle
     * a los árbitros de ese partido, en silencio (GenerarPagosJob solo deja
     * un Log::warning() cuando falta la tarifa de división+rol+formato, o
     * cuando las designaciones nunca se confirmaron). Sin este contador, el
     * ejecutivo solo se entera cuando un árbitro reclama que no le pagaron —
     * confirmado en la auditoría de carga de julio 2026 (docs/auditoria-carga-2026-07.md):
     * un colegio de prueba con tarifas sin configurar perdió el 100% de su
     * nómina (100 partidos finalizados, 0 movimientos) sin ningún error visible.
     *
     * @return int  Cantidad de partidos finalizados sin ningún movimiento de nómina generado.
     */
    public function partidosNominaSinGenerar(int $idColegio): int
    {
        return Partido::where('idColegio', $idColegio)
            ->where('estadoPartido', Partido::ESTADO_FINALIZADO)
            ->where('modalidadPago', 'nomina')
            ->whereDoesntHave('movimientosFinancieros', fn ($q) => $q->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO))
            ->count();
    }

    // ── Helpers privados ──────────────────

    /** Subquery: total abonado (no anulado) por movimiento, para LEFT JOIN. */
    private function subqueryAbonado(): \Illuminate\Database\Query\Builder
    {
        return DB::table('abonos_movimiento')
            ->selectRaw('idMovimiento, SUM(monto) as abonado')
            ->where('anulado', false)
            ->groupBy('idMovimiento');
    }

    /** Dinero real movido (excluye compensaciones internas) para un tipo de movimiento. */
    private function totalAbonosReales(int $idColegio, string $tipoMovimiento): float
    {
        return (float) AbonoMovimiento::whereHas('movimiento', fn ($q) => $q->where('idColegio', $idColegio)->where('tipoMovimiento', $tipoMovimiento))
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_NOMINA)
            ->sum('monto');
    }

    /**
     * Totales del período inmediatamente anterior de igual duración, con las
     * variaciones porcentuales respecto al período actual.
     */
    private function comparativaPeriodoAnterior(int $idColegio, string $desde, string $hasta, float $ingresosActual, float $egresosActual): array
    {
        $dDesde = Carbon::parse($desde);
        $dHasta = Carbon::parse($hasta);
        $dias   = (int) $dDesde->diffInDays($dHasta) + 1;

        $antHasta = $dDesde->copy()->subDay();
        $antDesde = $antHasta->copy()->subDays($dias - 1);

        $fila = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->whereBetween('fechaMovimiento', [$antDesde->toDateString(), $antHasta->toDateString()])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'ingreso' THEN montoTotal END), 0) as ingresos,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'egreso'  THEN montoTotal END), 0) as egresos
            ")
            ->first();

        $ingresosAnterior = (float) $fila->ingresos;
        $egresosAnterior  = (float) $fila->egresos;

        $variacion = fn (float $actual, float $anterior): ?float => $anterior > 0.0
            ? round(($actual - $anterior) / $anterior * 100, 1)
            : null;

        return [
            'desde'             => $antDesde->toDateString(),
            'hasta'             => $antHasta->toDateString(),
            'totalIngresos'     => $ingresosAnterior,
            'totalEgresos'      => $egresosAnterior,
            'neto'              => $ingresosAnterior - $egresosAnterior,
            'variacionIngresos' => $variacion($ingresosActual, $ingresosAnterior),
            'variacionEgresos'  => $variacion($egresosActual, $egresosAnterior),
        ];
    }
}
