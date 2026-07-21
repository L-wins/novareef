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
     *     idArbitro?: ?string|int, idTorneo?: ?string|int, idLoteCobro?: ?string,
     *     q?: ?string, desde?: ?string, hasta?: ?string}  $filtros
     */
    public function queryFiltrada(int $idColegio, array $filtros): Builder
    {
        return MovimientoFinanciero::where('movimientos_financieros.idColegio', $idColegio)
            ->when(! empty($filtros['tipoMovimiento']), fn ($q) => $q->where('tipoMovimiento', $filtros['tipoMovimiento']))
            ->when(! empty($filtros['categoria']), fn ($q) => $q->where('categoria', $filtros['categoria']))
            ->when(! empty($filtros['estado']), fn ($q) => $q->where('estadoMovimiento', $filtros['estado']))
            ->when(! empty($filtros['idArbitro']), fn ($q) => $q->where('idArbitro', (int) $filtros['idArbitro']))
            ->when(! empty($filtros['idTorneo']), fn ($q) => $q->where('idTorneo', (int) $filtros['idTorneo']))
            ->when(! empty($filtros['idLoteCobro']), fn ($q) => $q->where('idLoteCobro', $filtros['idLoteCobro']))
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
     * Movimientos institucionales del colegio (sin árbitro asociado) con los
     * mismos filtros que queryFiltrada — la vista de "Gastos e ingresos".
     */
    public function queryInstitucional(int $idColegio, array $filtros): Builder
    {
        return $this->queryFiltrada($idColegio, $filtros)
            ->whereNull('idArbitro')
            ->whereIn('categoria', MovimientoFinanciero::CATEGORIAS_INSTITUCIONALES);
    }

    /**
     * Totales del listado institucional filtrado: ingresos, egresos y neto.
     * No hay "pendiente" que calcular acá — estos movimientos nacen pagados
     * (ver FinanzasService::registrarMovimientoPagado()), así que siempre
     * sería cero.
     */
    public function resumenInstitucional(int $idColegio, array $filtros): array
    {
        $fila = $this->queryInstitucional($idColegio, $filtros)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'ingreso' AND estadoMovimiento != 'anulado' THEN montoTotal END), 0) as totalIngresos,
                COALESCE(SUM(CASE WHEN tipoMovimiento = 'egreso'  AND estadoMovimiento != 'anulado' THEN montoTotal END), 0) as totalEgresos
            ")
            ->first();

        return [
            'totalIngresos'  => (float) $fila->totalIngresos,
            'totalEgresos'   => (float) $fila->totalEgresos,
            'neto'           => (float) $fila->totalIngresos - (float) $fila->totalEgresos,
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

    /** Categorías de egreso que representan pago a un árbitro por su labor. */
    private const CATEGORIAS_PAGO_ARBITRO = [
        MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
        MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO,
    ];

    /** Categorías de ingreso que representan una deuda del árbitro hacia el colegio. */
    private const CATEGORIAS_DEUDA_ARBITRO = [
        MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
        MovimientoFinanciero::CATEGORIA_MULTA,
    ];

    /**
     * Egresos de nómina/externo del árbitro aún no saldados por completo, con
     * el partido y torneo de origen — lado "se le debe" del estado de cuenta.
     *
     * @return Collection<int, MovimientoFinanciero>
     */
    private function egresosPendientesArbitro(Arbitro $arbitro): Collection
    {
        return MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->whereIn('categoria', self::CATEGORIAS_PAGO_ARBITRO)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->with(['partido', 'torneo', 'historial.usuarioAccion'])
            ->conTotalAbonado()
            ->get()
            ->filter(fn (MovimientoFinanciero $m) => $m->saldoPendiente() > 0.0)
            ->sortBy('fechaMovimiento')
            ->values();
    }

    /**
     * Mensualidades/multas del árbitro aún no saldadas — lado "nos debe" del
     * estado de cuenta, simétrico a egresosPendientesArbitro().
     *
     * @return Collection<int, MovimientoFinanciero>
     */
    private function ingresosPendientesArbitro(Arbitro $arbitro): Collection
    {
        return MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->whereIn('categoria', self::CATEGORIAS_DEUDA_ARBITRO)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->with('historial.usuarioAccion')
            ->conTotalAbonado()
            ->get()
            ->filter(fn (MovimientoFinanciero $m) => $m->saldoPendiente() > 0.0)
            ->sortBy('fechaMovimiento')
            ->values();
    }

    /** Saldo total pendiente por cobrar del árbitro (lo que se le debe) — usado por el badge del perfil. */
    public function saldoPendienteArbitro(Arbitro $arbitro): float
    {
        return $this->egresosPendientesArbitro($arbitro)->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());
    }

    /** Saldo total que el árbitro le debe al colegio (mensualidad/multa pendiente). */
    public function saldoPorCobrarArbitro(Arbitro $arbitro): float
    {
        return $this->ingresosPendientesArbitro($arbitro)->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());
    }

    /**
     * Estado de cuenta completo del árbitro en las dos direcciones: lo que el
     * colegio le debe (nómina/externo pendiente) y lo que el árbitro le debe
     * al colegio (mensualidad/multa pendiente), con historial de pagos en
     * ambos sentidos y descuentos por compensación de nómina. Un solo método
     * alimenta tanto /mi-estado-cuenta (autoservicio del árbitro) como la
     * ficha financiera que el tesorero ve de cualquier árbitro.
     *
     * @return array{
     *     saldoPendienteCobrar: float,
     *     pendientesPorPartido: Collection<int, MovimientoFinanciero>,
     *     saldoPorCobrar: float,
     *     pendientesPorCuota: Collection<int, MovimientoFinanciero>,
     *     historialPagos: Collection<int, AbonoMovimiento>,
     *     historialPagosHechos: Collection<int, AbonoMovimiento>,
     *     historialMultas: Collection<int, MovimientoFinanciero>,
     *     descuentosNomina: Collection<int, AbonoMovimiento>,
     * }
     */
    public function estadoCuentaArbitro(Arbitro $arbitro): array
    {
        $pendientesPorPartido = $this->egresosPendientesArbitro($arbitro);
        $saldoPendienteCobrar = (float) $pendientesPorPartido->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

        $pendientesPorCuota = $this->ingresosPendientesArbitro($arbitro);
        $saldoPorCobrar     = (float) $pendientesPorCuota->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

        $historialPagos = AbonoMovimiento::whereHas('movimiento', function ($q) use ($arbitro): void {
                $q->where('idArbitro', $arbitro->idArbitro)->whereIn('categoria', self::CATEGORIAS_PAGO_ARBITRO);
            })
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_NOMINA)
            ->with('movimiento.torneo')
            ->orderByDesc('fechaAbono')
            ->get();

        $historialPagosHechos = AbonoMovimiento::whereHas('movimiento', function ($q) use ($arbitro): void {
                $q->where('idArbitro', $arbitro->idArbitro)->whereIn('categoria', self::CATEGORIAS_DEUDA_ARBITRO);
            })
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_NOMINA)
            ->with('movimiento')
            ->orderByDesc('fechaAbono')
            ->get();

        $historialMultas = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MULTA)
            ->orderByDesc('fechaMovimiento')
            ->get();

        $descuentosNomina = AbonoMovimiento::whereHas('movimiento', fn ($q) => $q->where('idArbitro', $arbitro->idArbitro))
            ->where('metodoPago', AbonoMovimiento::METODO_NOMINA)
            ->where('anulado', false)
            ->with('movimiento')
            ->orderByDesc('fechaAbono')
            ->get();

        return [
            'saldoPendienteCobrar' => $saldoPendienteCobrar,
            'pendientesPorPartido' => $pendientesPorPartido,
            'saldoPorCobrar'       => $saldoPorCobrar,
            'pendientesPorCuota'   => $pendientesPorCuota,
            'historialPagos'       => $historialPagos,
            'historialPagosHechos' => $historialPagosHechos,
            'historialMultas'      => $historialMultas,
            'descuentosNomina'     => $descuentosNomina,
        ];
    }

    /**
     * "Bolsillos" del colegio — a partir de balanceGeneral() separa la caja
     * bruta de lo realmente disponible: `disponibleReal` es cuánto del saldo
     * en caja NO está ya comprometido con nómina pendiente de árbitros. Las
     * deudas de árbitros hacia el colegio no cuentan porque todavía no son
     * caja.
     *
     * Antes componía balanceGeneral() + resumenListado() sin filtros — dos
     * agregados completos calculando el mismo número por caminos SQL
     * distintos. Desde que gastos/ingresos institucionales nacen pagados
     * (FinanzasService::registrarMovimientoPagado()), esas categorías nunca
     * aportan nada a "pendiente", así que resumenListado()['pendientePorPagar']
     * y ['pendientePorCobrar'] siempre terminaban siendo exactamente
     * totalLeDebemos/totalNosDeben de balanceGeneral() — se eliminó la
     * segunda consulta y se derivan directamente de ahí.
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
     * Datos del comprobante de un pago acumulado (lote): árbitro, abonos de
     * nómina desembolsados, deudas compensadas y totales. Devuelve null si el
     * lote no existe para ese colegio.
     *
     * @return ?array{
     *     arbitro: Arbitro, fecha: Carbon, metodoPago: string,
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

        $desembolsoReal = $pagosNomina->firstWhere('metodoPago', '!=', AbonoMovimiento::METODO_NOMINA);

        return [
            'arbitro'           => $arbitro,
            'fecha'             => $abonos->first()->fechaAbono,
            'metodoPago'        => $desembolsoReal->metodoPago ?? AbonoMovimiento::METODO_NOMINA,
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
     * Datos del recibo de un cobro (mensualidad/otro_ingreso) hecho vía
     * Cobro Masivo — dirección inversa a datosComprobante() (ahí el colegio
     * le paga al árbitro; acá el árbitro le paga al colegio), por eso es un
     * método aparte y no una rama del mismo: forzarlo por el mismo builder
     * produciría un comprobante con el texto narrado al revés. Un
     * idLotePago de cobro masivo puede abarcar varios árbitros a la vez, así
     * que se filtra también por $idArbitro para el recibo individual.
     *
     * @return ?array{
     *     arbitro: Arbitro, fecha: Carbon, metodoPago: string,
     *     conceptos: Collection<int, array{concepto: string, monto: float}>, total: float,
     * }
     */
    public function datosComprobanteCobro(string $idLotePago, int $idColegio, int $idArbitro): ?array
    {
        $abonos = AbonoMovimiento::where('idLotePago', $idLotePago)
            ->where('idColegio', $idColegio)
            ->where('anulado', false)
            ->whereHas('movimiento', fn (Builder $q) => $q
                ->where('idArbitro', $idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_OTRO_INGRESO]))
            ->with('movimiento.arbitro.usuario')
            ->orderBy('idAbono')
            ->get();

        if ($abonos->isEmpty()) {
            return null;
        }

        $arbitro = $abonos->first()->movimiento?->arbitro;
        if ($arbitro === null) {
            return null;
        }

        return [
            'arbitro'    => $arbitro,
            'fecha'      => $abonos->first()->fechaAbono,
            'metodoPago' => $abonos->first()->metodoPago,
            'conceptos'  => $abonos->map(fn (AbonoMovimiento $a) => [
                'concepto' => $a->movimiento?->concepto ?? '—',
                'monto'    => (float) $a->monto,
            ])->values(),
            'total' => (float) $abonos->sum('monto'),
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
            ", [AbonoMovimiento::METODO_NOMINA])
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

    /**
     * Archivo de comprobantes de un mes calendario: une lotes de pago de
     * nómina (incluye los que compensaron una deuda de paso — ese detalle ya
     * lo separa datosComprobante()) y lotes de cobro de mensualidad, cada
     * fila agrupada por lote+árbitro. "esNomina" se decide por si el lote
     * tocó alguna categoría de nómina — un lote de compensarDeudaConNomina()
     * mezcla la deuda compensada y el pago de nómina bajo el mismo
     * idLotePago para el mismo árbitro, y ese caso ya se narra completo en
     * el comprobante de nómina existente, así que se enruta ahí.
     *
     * @return Collection<int, array{idLotePago: string, tipo: string, arbitro: ?Arbitro, monto: float, fecha: string}>
     */
    public function comprobantesDelMes(int $idColegio, string $mes): Collection
    {
        $fecha = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();

        $filas = AbonoMovimiento::query()
            ->join('movimientos_financieros', 'movimientos_financieros.idMovimiento', '=', 'abonos_movimiento.idMovimiento')
            ->where('movimientos_financieros.idColegio', $idColegio)
            ->where('abonos_movimiento.anulado', false)
            ->whereNotNull('abonos_movimiento.idLotePago')
            ->whereBetween('abonos_movimiento.fechaAbono', [$fecha->toDateString(), $fecha->copy()->endOfMonth()->toDateString()])
            ->selectRaw("
                abonos_movimiento.idLotePago as idLotePago,
                movimientos_financieros.idArbitro as idArbitro,
                MAX(abonos_movimiento.fechaAbono) as fecha,
                SUM(abonos_movimiento.monto) as monto,
                MAX(CASE WHEN movimientos_financieros.categoria IN ('nomina_arbitro', 'arbitro_externo') THEN 1 ELSE 0 END) as esNomina
            ")
            ->groupBy('abonos_movimiento.idLotePago', 'movimientos_financieros.idArbitro')
            ->orderByDesc('fecha')
            ->get();

        $arbitros = Arbitro::whereIn('idArbitro', $filas->pluck('idArbitro')->unique()->filter())
            ->with('usuario')
            ->get()
            ->keyBy('idArbitro');

        return $filas->map(fn ($fila) => [
            'idLotePago' => $fila->idLotePago,
            'tipo'       => $fila->esNomina ? 'nomina' : 'cobro',
            'arbitro'    => $arbitros->get($fila->idArbitro),
            'monto'      => (float) $fila->monto,
            'fecha'      => $fila->fecha,
        ])->values();
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
