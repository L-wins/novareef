<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbonoMovimiento;
use App\Models\Arbitro;
use App\Models\MovimientoFinanciero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Estado de cuenta de un árbitro individual (lo que se le debe / lo que
 * debe) y los comprobantes/recibos de pagos y cobros ya hechos. Extraído de
 * ReporteFinanzasService — ver BalanceFinanzasService para el porqué de la
 * división en 3 servicios.
 */
final class EstadoCuentaArbitroService
{
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
}
