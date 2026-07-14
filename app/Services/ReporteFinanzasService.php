<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbonoMovimiento;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de lectura/agregación de Finanzas (resumen del listado, reportes,
 * serie mensual y balance). Las operaciones de escritura (movimientos, abonos,
 * pagos acumulados) viven en FinanzasService.
 */
final class ReporteFinanzasService
{
    private const MESES_CORTOS = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Query base de movimientos del colegio con los filtros del listado.
     * La comparten el índice (paginación) y resumenListado() para que las
     * stat cards siempre reflejen exactamente lo que se está filtrando.
     *
     * @param  array{tipoMovimiento?: ?string, categoria?: ?string, estado?: ?string,
     *     idArbitro?: ?string|int, idTorneo?: ?string|int, q?: ?string, desde?: ?string, hasta?: ?string}  $filtros
     */
    public function queryFiltrada(int $idColegio, array $filtros): Builder
    {
        return MovimientoFinanciero::where('movimientos_financieros.idColegio', $idColegio)
            ->when(! empty($filtros['tipoMovimiento']), fn ($q) => $q->where('tipoMovimiento', $filtros['tipoMovimiento']))
            ->when(! empty($filtros['categoria']), fn ($q) => $q->where('categoria', $filtros['categoria']))
            ->when(! empty($filtros['estado']), fn ($q) => $q->where('estadoMovimiento', $filtros['estado']))
            ->when(! empty($filtros['idArbitro']), fn ($q) => $q->where('idArbitro', (int) $filtros['idArbitro']))
            ->when(! empty($filtros['idTorneo']), fn ($q) => $q->where('idTorneo', (int) $filtros['idTorneo']))
            ->when(! empty($filtros['q']), fn ($q) => $q->where('concepto', 'like', '%' . $filtros['q'] . '%'))
            ->when(! empty($filtros['desde']), fn ($q) => $q->where('fechaMovimiento', '>=', $filtros['desde']))
            ->when(! empty($filtros['hasta']), fn ($q) => $q->where('fechaMovimiento', '<=', $filtros['hasta']));
    }

    /**
     * Totales del listado filtrado en una sola query agregada: ingresos,
     * egresos, neto y saldos pendientes por cobrar/pagar. Los movimientos
     * anulados nunca suman (aunque el filtro de estado los muestre en la tabla).
     *
     * @return array{totalIngresos: float, totalEgresos: float, neto: float,
     *     pendientePorCobrar: float, pendientePorPagar: float, cantidad: int}
     */
    public function resumenListado(int $idColegio, array $filtros): array
    {
        $fila = $this->queryFiltrada($idColegio, $filtros)
            ->leftJoinSub($this->subqueryAbonado(), 'ab', 'ab.idMovimiento', '=', 'movimientos_financieros.idMovimiento')
            ->selectRaw("
                COUNT(*) as cantidad,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'ingreso' AND estadoMovimiento != 'anulado' AND categoria != 'saldo_inicial' THEN montoTotal END), 0) as totalIngresos,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'egreso'  AND estadoMovimiento != 'anulado' THEN montoTotal END), 0) as totalEgresos,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'ingreso' AND estadoMovimiento != 'anulado' THEN montoTotal - COALESCE(ab.abonado, 0) END), 0) as pendientePorCobrar,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'egreso'  AND estadoMovimiento != 'anulado' THEN montoTotal - COALESCE(ab.abonado, 0) END), 0) as pendientePorPagar
            ")
            ->first();

        return [
            'totalIngresos'      => (float) $fila->totalIngresos,
            'totalEgresos'       => (float) $fila->totalEgresos,
            'neto'               => (float) $fila->totalIngresos - (float) $fila->totalEgresos,
            'pendientePorCobrar' => (float) $fila->pendientePorCobrar,
            'pendientePorPagar'  => (float) $fila->pendientePorPagar,
            'cantidad'           => (int) $fila->cantidad,
        ];
    }

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

        $filas = MovimientoFinanciero::where('movimientos_financieros.idColegio', $idColegio)
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
                COALESCE(SUM(CASE WHEN categoria IN ('mensualidad', 'multa') THEN montoTotal - COALESCE(ab.abonado, 0) ELSE 0 END), 0) as nosDebe
            ")
            ->groupBy('idArbitro')
            ->havingRaw('leDebemos > 0 OR nosDebe > 0')
            ->get();

        $arbitros = Arbitro::whereIn('idArbitro', $filas->pluck('idArbitro'))
            ->with('usuario')
            ->get()
            ->keyBy('idArbitro');

        $porArbitro = $filas
            // Árbitros archivados (soft delete) no aparecen en el balance —
            // mismo comportamiento que la versión anterior, que iteraba
            // solo los árbitros activos del colegio.
            ->filter(fn ($fila) => $arbitros->has($fila->idArbitro))
            ->map(fn ($fila) => [
                'arbitro'   => $arbitros[$fila->idArbitro],
                'leDebemos' => (float) $fila->leDebemos,
                'nosDebe'   => (float) $fila->nosDebe,
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
     * "Bolsillos" del colegio — compone balanceGeneral() y resumenListado()
     * (sin filtros) para separar la caja bruta de lo realmente disponible:
     * `disponibleReal` es cuánto del saldo en caja NO está ya comprometido
     * con pagos pendientes (nómina de árbitros + gastos institucionales/fijos/
     * varios pendientes). Las deudas de árbitros hacia el colegio no cuentan
     * porque todavía no son caja.
     *
     * @return array{saldoEnCaja: float, disponibleReal: float, pendientePorCobrar: float, pendientePorPagar: float}
     */
    public function bolsillos(int $idColegio): array
    {
        $balance = $this->balanceGeneral($idColegio);
        $global  = $this->resumenListado($idColegio, []);

        return [
            'saldoEnCaja'        => $balance['saldoEnCaja'],
            'disponibleReal'     => $balance['saldoEnCaja'] - $global['pendientePorPagar'],
            'pendientePorCobrar' => $global['pendientePorCobrar'],
            'pendientePorPagar'  => $global['pendientePorPagar'],
        ];
    }

    /**
     * Datos del comprobante de un pago acumulado (lote): árbitro, abonos de
     * nómina desembolsados, deudas compensadas y totales. Devuelve null si el
     * lote no existe para ese colegio.
     *
     * @return ?array{
     *     arbitro: Arbitro, fecha: Carbon, metodoPago: string, referencia: ?string,
     *     pagosNomina: Collection<int, AbonoMovimiento>, deudasCompensadas: Collection<int, AbonoMovimiento>,
     *     totalNomina: float, totalDeudas: float, netoDesembolsado: float,
     * }
     */
    public function datosComprobante(string $idLotePago, int $idColegio): ?array
    {
        $abonos = AbonoMovimiento::where('idLotePago', $idLotePago)
            ->where('idColegio', $idColegio)
            ->where('anulado', false)
            ->with(['movimiento.arbitro.usuario', 'movimiento.torneo'])
            ->orderBy('idAbono')
            ->get();

        if ($abonos->isEmpty()) {
            return null;
        }

        $arbitro = $abonos->first()->movimiento?->arbitro;
        if ($arbitro === null) {
            return null;
        }

        $esDeuda = fn (AbonoMovimiento $a): bool => in_array($a->movimiento?->categoria, [
            MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
            MovimientoFinanciero::CATEGORIA_MULTA,
        ], true);

        // Sobre la nómina puede haber dos abonos por movimiento (compensación
        // + desembolso real) — para el comprobante se agrupan por movimiento.
        $pagosNomina       = $abonos->reject($esDeuda);
        $deudasCompensadas = $abonos->filter($esDeuda)->values();

        $totalNomina = (float) $pagosNomina->sum('monto');
        $totalDeudas = (float) $deudasCompensadas->sum('monto');

        $desembolsoReal = $pagosNomina->firstWhere('metodoPago', '!=', AbonoMovimiento::METODO_COMPENSACION_NOMINA);

        return [
            'arbitro'           => $arbitro,
            'fecha'             => $abonos->first()->fechaAbono,
            'metodoPago'        => $desembolsoReal->metodoPago ?? AbonoMovimiento::METODO_COMPENSACION_NOMINA,
            'referencia'        => $desembolsoReal->referencia ?? null,
            'pagosNomina'       => $pagosNomina->groupBy('idMovimiento')->map(fn (Collection $grupo) => (object) [
                'movimiento' => $grupo->first()->movimiento,
                'monto'      => (float) $grupo->sum('monto'),
            ])->values(),
            'deudasCompensadas' => $deudasCompensadas,
            'totalNomina'       => $totalNomina,
            'totalDeudas'       => $totalDeudas,
            'netoDesembolsado'  => $totalNomina - $totalDeudas,
        ];
    }

    /**
     * Últimos lotes de pago acumulado de un árbitro (para la lista de
     * comprobantes descargables en la vista de pagos).
     *
     * @return Collection<int, object{idLotePago: string, fecha: string, neto: float}>
     */
    public function lotesRecientes(int $idColegio, int $idArbitro, int $limite = 5): Collection
    {
        return AbonoMovimiento::where('abonos_movimiento.idColegio', $idColegio)
            ->whereNotNull('idLotePago')
            ->where('anulado', false)
            ->whereHas('movimiento', fn ($q) => $q->where('idArbitro', $idArbitro))
            ->selectRaw("
                idLotePago,
                MAX(fechaAbono) as fecha,
                COALESCE(SUM(CASE WHEN metodoPago != ? THEN monto ELSE 0 END), 0) as desembolsado
            ", [AbonoMovimiento::METODO_COMPENSACION_NOMINA])
            ->groupBy('idLotePago')
            ->orderByDesc('fecha')
            ->limit($limite)
            ->get()
            ->map(fn ($fila) => (object) [
                'idLotePago' => $fila->idLotePago,
                'fecha'      => $fila->fecha,
                'neto'       => (float) $fila->desembolsado,
            ]);
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
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_COMPENSACION_NOMINA)
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
