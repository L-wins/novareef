<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MovimientoFinanciero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Listado filtrado de movimientos financieros (índice + resumen de stat
 * cards) y movimientos institucionales (gastos/ingresos sin árbitro
 * asociado). El balance general/reportes por rango viven en
 * BalanceFinanzasService, y el estado de cuenta/comprobantes por árbitro en
 * EstadoCuentaArbitroService — ver el comentario de BalanceFinanzasService
 * para el porqué de la división (este archivo superaba las ~600-700 líneas
 * documentadas en CLAUDE.md).
 */
final class ReporteFinanzasService
{
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

    /** Subquery: total abonado (no anulado) por movimiento, para LEFT JOIN. */
    private function subqueryAbonado(): \Illuminate\Database\Query\Builder
    {
        return DB::table('abonos_movimiento')
            ->selectRaw('idMovimiento, SUM(monto) as abonado')
            ->where('anulado', false)
            ->groupBy('idMovimiento');
    }
}
