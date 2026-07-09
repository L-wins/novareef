<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\NotificarPagoArbitroJob;
use App\Models\AbonoMovimiento;
use App\Models\Arbitro;
use App\Models\HistorialMovimientoFinanciero;
use App\Models\MovimientoFinanciero;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FinanzasService
{
    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

    /**
     * Registra un movimiento financiero (ingreso o egreso) en estado
     * pendiente y deja constancia en el historial.
     *
     * @param  array{
     *     tipoMovimiento: string, categoria: string, concepto: string,
     *     montoTotal: float|string, fechaMovimiento: string,
     *     idArbitro?: ?int, nombreArbitroExterno?: ?string, documentoArbitroExterno?: ?string,
     *     idTorneo?: ?int, idPartido?: ?int, idDesignacion?: ?int,
     *     tipoOrigenMulta?: ?string, idOrigenMulta?: ?int, observaciones?: ?string,
     * }  $datos
     *
     * @param  ?User  $usuario  Null cuando el movimiento lo genera un job del
     *     sistema (ej. finalización automática de un partido nómina) sin
     *     ningún usuario detrás — mismo criterio que idUsuarioAccion nulo en
     *     HistorialDesignacion para transiciones disparadas por jobs.
     *
     * @throws \RuntimeException  Si la categoría no corresponde al tipo de movimiento.
     */
    public function registrarMovimiento(int $idColegio, array $datos, ?User $usuario): MovimientoFinanciero
    {
        $tipo      = $datos['tipoMovimiento'];
        $categoria = $datos['categoria'];

        $categoriasValidas = MovimientoFinanciero::CATEGORIAS_POR_TIPO[$tipo] ?? [];
        if (! in_array($categoria, $categoriasValidas, true)) {
            throw new \RuntimeException("La categoría «{$categoria}» no corresponde a un movimiento de tipo «{$tipo}».");
        }

        return DB::transaction(function () use ($idColegio, $datos, $usuario, $tipo, $categoria): MovimientoFinanciero {
            $movimiento = MovimientoFinanciero::create([
                'idColegio'               => $idColegio,
                'tipoMovimiento'          => $tipo,
                'categoria'               => $categoria,
                'concepto'                => $datos['concepto'],
                'montoTotal'              => $datos['montoTotal'],
                'estadoMovimiento'        => MovimientoFinanciero::ESTADO_PENDIENTE,
                'fechaMovimiento'         => $datos['fechaMovimiento'],
                'idArbitro'               => $datos['idArbitro'] ?? null,
                'nombreArbitroExterno'    => $datos['nombreArbitroExterno'] ?? null,
                'documentoArbitroExterno' => $datos['documentoArbitroExterno'] ?? null,
                'idTorneo'                => $datos['idTorneo'] ?? null,
                'idPartido'               => $datos['idPartido'] ?? null,
                'idDesignacion'           => $datos['idDesignacion'] ?? null,
                'tipoOrigenMulta'         => $datos['tipoOrigenMulta'] ?? null,
                'idOrigenMulta'           => $datos['idOrigenMulta'] ?? null,
                'idUsuarioRegistro'       => $usuario?->idUsuario,
                'observaciones'           => $datos['observaciones'] ?? null,
            ]);

            HistorialMovimientoFinanciero::create([
                'idMovimiento'    => $movimiento->idMovimiento,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => $usuario?->idUsuario,
                'tipoAccion'      => HistorialMovimientoFinanciero::TIPO_CREADO,
                'estadoNuevo'     => MovimientoFinanciero::ESTADO_PENDIENTE,
                'detalle'         => "Movimiento registrado: {$movimiento->concepto}",
            ]);

            return $movimiento;
        });
    }

    /**
     * Registra un abono (pago parcial o total) sobre un movimiento existente
     * y recalcula su estado (pendiente/parcial/pagado).
     *
     * @param  array{monto: float|string, fechaAbono: string, metodoPago: string,
     *     referencia?: ?string, idLotePago?: ?string, observaciones?: ?string}  $datos
     *
     * @throws \RuntimeException  Si el movimiento está anulado o el abono excede el saldo pendiente.
     */
    public function registrarAbono(MovimientoFinanciero $movimiento, array $datos, User $usuario): AbonoMovimiento
    {
        if ($movimiento->estaAnulado()) {
            throw new \RuntimeException('No se puede abonar un movimiento anulado.');
        }

        return DB::transaction(function () use ($movimiento, $datos, $usuario): AbonoMovimiento {
            $movimiento->refresh();

            $saldoPendiente = $movimiento->saldoPendiente();
            $monto          = (float) $datos['monto'];

            if ($monto <= 0) {
                throw new \RuntimeException('El monto del abono debe ser mayor a cero.');
            }

            if ($monto > $saldoPendiente) {
                throw new \RuntimeException(sprintf(
                    'El abono (%s) supera el saldo pendiente del movimiento (%s).',
                    number_format($monto, 2),
                    number_format($saldoPendiente, 2),
                ));
            }

            $abono = AbonoMovimiento::create([
                'idMovimiento'       => $movimiento->idMovimiento,
                'idColegio'          => $movimiento->idColegio,
                'monto'              => $monto,
                'fechaAbono'         => $datos['fechaAbono'],
                'metodoPago'         => $datos['metodoPago'],
                'referencia'         => $datos['referencia'] ?? null,
                'idLotePago'         => $datos['idLotePago'] ?? null,
                'idUsuarioRegistro'  => $usuario->idUsuario,
                'observaciones'      => $datos['observaciones'] ?? null,
            ]);

            $estadoAnterior = $movimiento->estadoMovimiento;
            $nuevoSaldo     = $saldoPendiente - $monto;
            $estadoNuevo    = $nuevoSaldo <= 0.0
                ? MovimientoFinanciero::ESTADO_PAGADO
                : MovimientoFinanciero::ESTADO_PARCIAL;

            $movimiento->update(['estadoMovimiento' => $estadoNuevo]);

            $esCompensacion = $datos['metodoPago'] === AbonoMovimiento::METODO_COMPENSACION_NOMINA;

            HistorialMovimientoFinanciero::create([
                'idMovimiento'    => $movimiento->idMovimiento,
                'idColegio'       => $movimiento->idColegio,
                'idUsuarioAccion' => $usuario->idUsuario,
                'tipoAccion'      => $esCompensacion
                    ? HistorialMovimientoFinanciero::TIPO_COMPENSADO
                    : HistorialMovimientoFinanciero::TIPO_ABONADO,
                'estadoAnterior'  => $estadoAnterior,
                'estadoNuevo'     => $estadoNuevo,
                'detalle'         => "Abono de {$monto} vía {$datos['metodoPago']}",
            ]);

            return $abono;
        });
    }

    /**
     * Anula un movimiento financiero. Solo se permite si todavía no tiene
     * ningún abono registrado — si ya se pagó algo, el movimiento queda como
     * registro contable permanente, sin mecanismo de reversión.
     *
     * @throws \RuntimeException  Si el movimiento ya tiene abonos o ya está anulado.
     */
    public function anularMovimiento(MovimientoFinanciero $movimiento, User $usuario, ?string $motivo = null): void
    {
        if ($movimiento->estaAnulado()) {
            throw new \RuntimeException('El movimiento ya está anulado.');
        }

        if ($movimiento->abonos()->where('anulado', false)->exists()) {
            throw new \RuntimeException('No se puede anular un movimiento que ya tiene abonos registrados.');
        }

        DB::transaction(function () use ($movimiento, $usuario, $motivo): void {
            $estadoAnterior = $movimiento->estadoMovimiento;

            $movimiento->update(['estadoMovimiento' => MovimientoFinanciero::ESTADO_ANULADO]);

            HistorialMovimientoFinanciero::create([
                'idMovimiento'    => $movimiento->idMovimiento,
                'idColegio'       => $movimiento->idColegio,
                'idUsuarioAccion' => $usuario->idUsuario,
                'tipoAccion'      => HistorialMovimientoFinanciero::TIPO_ANULADO,
                'estadoAnterior'  => $estadoAnterior,
                'estadoNuevo'     => MovimientoFinanciero::ESTADO_ANULADO,
                'detalle'         => $motivo,
            ]);
        });
    }

    /**
     * Genera los movimientos financieros al finalizar un partido en
     * modalidad nómina: un egreso "nómina de árbitro" por cada designación
     * confirmada (valor resuelto vía DesignacionService::calcularPago) y un
     * ingreso "torneo" con la suma de esos valores — el colegio actúa de
     * intermediario, sin margen (passthrough).
     *
     * En modalidad 'campo' no genera nada: el árbitro cobra en efectivo
     * directo del organizador y el colegio no gestiona ese dinero.
     *
     * Idempotente — si ya existen movimientos para este partido (el job que
     * la invoca puede reintentarse), no vuelve a generarlos.
     */
    public function generarMovimientosPorFinalizacionPartido(Partido $partido): void
    {
        if ($partido->modalidadPago !== 'nomina') {
            return;
        }

        $yaGenerado = MovimientoFinanciero::where('idPartido', $partido->idPartido)
            ->whereIn('categoria', [
                MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO,
                MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
            ])
            ->exists();

        if ($yaGenerado) {
            return;
        }

        $designacionesConfirmadas = $partido->designacionesConfirmadas()->with('rol')->get();

        DB::transaction(function () use ($partido, $designacionesConfirmadas): void {
            $totalIngreso = 0.0;

            foreach ($designacionesConfirmadas as $designacion) {
                $pago  = $this->designaciones->calcularPago($designacion);
                $valor = $pago['valor'];

                if ($valor === null) {
                    Log::warning("FinanzasService: sin tarifa configurada para la designación {$designacion->idDesignacion} del partido {$partido->idPartido} — se omite del pago de nómina.");
                    continue;
                }

                $this->registrarMovimiento($partido->idColegio, [
                    'tipoMovimiento'  => MovimientoFinanciero::TIPO_EGRESO,
                    'categoria'       => MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
                    'concepto'        => "Nómina — {$designacion->rol?->nombre} — partido #{$partido->idPartido}",
                    'montoTotal'      => $valor,
                    'fechaMovimiento' => $partido->fechaPartido,
                    'idArbitro'       => $designacion->idArbitro,
                    'idTorneo'        => $partido->idTorneo,
                    'idPartido'       => $partido->idPartido,
                    'idDesignacion'   => $designacion->idDesignacion,
                ], null);

                $totalIngreso += $valor;
            }

            if ($totalIngreso > 0.0) {
                $this->registrarMovimiento($partido->idColegio, [
                    'tipoMovimiento'  => MovimientoFinanciero::TIPO_INGRESO,
                    'categoria'       => MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO,
                    'concepto'        => "Ingreso por partido #{$partido->idPartido}",
                    'montoTotal'      => $totalIngreso,
                    'fechaMovimiento' => $partido->fechaPartido,
                    'idTorneo'        => $partido->idTorneo,
                    'idPartido'       => $partido->idPartido,
                ], null);
            }
        });
    }

    /**
     * Estado de cuenta del árbitro: saldo pendiente por cobrar, historial de
     * pagos recibidos, historial de multas y descuentos aplicados en nómina
     * (compensación automática de deudas al momento del pago acumulado).
     *
     * @return array{
     *     saldoPendienteCobrar: float,
     *     historialPagos: Collection<int, AbonoMovimiento>,
     *     historialMultas: Collection<int, MovimientoFinanciero>,
     *     descuentosNomina: Collection<int, AbonoMovimiento>,
     * }
     */
    public function estadoCuentaArbitro(Arbitro $arbitro): array
    {
        $categoriasPagoArbitro = [
            MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
            MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO,
        ];

        $egresosArbitro = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->whereIn('categoria', $categoriasPagoArbitro)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->get();

        $saldoPendienteCobrar = $egresosArbitro->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

        $historialPagos = AbonoMovimiento::whereHas('movimiento', function ($q) use ($arbitro, $categoriasPagoArbitro): void {
                $q->where('idArbitro', $arbitro->idArbitro)->whereIn('categoria', $categoriasPagoArbitro);
            })
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_COMPENSACION_NOMINA)
            ->with('movimiento.torneo')
            ->orderByDesc('fechaAbono')
            ->get();

        $historialMultas = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_MULTA)
            ->orderByDesc('fechaMovimiento')
            ->get();

        $descuentosNomina = AbonoMovimiento::whereHas('movimiento', fn ($q) => $q->where('idArbitro', $arbitro->idArbitro))
            ->where('metodoPago', AbonoMovimiento::METODO_COMPENSACION_NOMINA)
            ->where('anulado', false)
            ->with('movimiento')
            ->orderByDesc('fechaAbono')
            ->get();

        return [
            'saldoPendienteCobrar' => $saldoPendienteCobrar,
            'historialPagos'       => $historialPagos,
            'historialMultas'      => $historialMultas,
            'descuentosNomina'     => $descuentosNomina,
        ];
    }

    /**
     * Pago acumulado del tesorero a un árbitro: salda uno o varios egresos de
     * nómina pendientes, netando primero contra las deudas (mensualidad/multa)
     * que el tesorero elija incluir — las deudas seleccionadas siempre se
     * saldan por completo, y el sobrante de nómina se desembolsa con el
     * método de pago real indicado.
     *
     * @param  int[]  $idsMovimientosNomina  Egresos nomina_arbitro/arbitro_externo pendientes a pagar.
     * @param  int[]  $idsDeudasNetear       Mensualidad/multa pendientes del árbitro a compensar (opcional, puede ir vacío).
     * @param  array{fecha: string, metodoPago: string, referencia?: ?string}  $datosPago
     *
     * @return array{totalNomina: float, totalDeudas: float, netoDesembolsado: float, idLotePago: string}
     *
     * @throws \RuntimeException  Si no hay nómina seleccionada o las deudas superan el saldo de nómina.
     */
    public function pagarAcumuladoArbitro(Arbitro $arbitro, array $idsMovimientosNomina, array $idsDeudasNetear, array $datosPago, User $usuario): array
    {
        return DB::transaction(function () use ($arbitro, $idsMovimientosNomina, $idsDeudasNetear, $datosPago, $usuario): array {
            $movimientosNomina = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                ->whereIn('idMovimiento', $idsMovimientosNomina)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                ->lockForUpdate()
                ->get();

            if ($movimientosNomina->isEmpty()) {
                throw new \RuntimeException('Selecciona al menos un pago de nómina pendiente.');
            }

            $deudas = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                ->whereIn('idMovimiento', $idsDeudasNetear)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_MULTA])
                ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                ->lockForUpdate()
                ->get();

            $totalNomina = $movimientosNomina->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());
            $totalDeudas = $deudas->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

            if ($totalDeudas > $totalNomina) {
                throw new \RuntimeException(sprintf(
                    'Las deudas seleccionadas (%s) superan el saldo de nómina seleccionado (%s).',
                    number_format($totalDeudas, 2),
                    number_format($totalNomina, 2),
                ));
            }

            $idLotePago = (string) Str::uuid();

            // Las deudas seleccionadas siempre se saldan por completo.
            foreach ($deudas as $deuda) {
                $this->registrarAbono($deuda, [
                    'monto'         => $deuda->saldoPendiente(),
                    'fechaAbono'    => $datosPago['fecha'],
                    'metodoPago'    => AbonoMovimiento::METODO_COMPENSACION_NOMINA,
                    'idLotePago'    => $idLotePago,
                    'observaciones' => "Compensado contra pago de nómina del {$datosPago['fecha']}",
                ], $usuario);
            }

            // Se distribuye la compensación entre los egresos de nómina, en el
            // orden seleccionado, hasta agotarla; el resto de cada uno se paga
            // con el método real que ingresó el tesorero.
            $restanteCompensacion = $totalDeudas;

            foreach ($movimientosNomina as $movimiento) {
                $saldo = $movimiento->saldoPendiente();
                if ($saldo <= 0.0) {
                    continue;
                }

                $montoCompensado = min($restanteCompensacion, $saldo);
                if ($montoCompensado > 0.0) {
                    $this->registrarAbono($movimiento, [
                        'monto'         => $montoCompensado,
                        'fechaAbono'    => $datosPago['fecha'],
                        'metodoPago'    => AbonoMovimiento::METODO_COMPENSACION_NOMINA,
                        'idLotePago'    => $idLotePago,
                        'observaciones' => 'Compensado contra deudas pendientes del árbitro',
                    ], $usuario);
                    $restanteCompensacion -= $montoCompensado;
                }

                $remanente = $saldo - $montoCompensado;
                if ($remanente > 0.0) {
                    $this->registrarAbono($movimiento, [
                        'monto'      => $remanente,
                        'fechaAbono' => $datosPago['fecha'],
                        'metodoPago' => $datosPago['metodoPago'],
                        'referencia' => $datosPago['referencia'] ?? null,
                        'idLotePago' => $idLotePago,
                    ], $usuario);
                }
            }

            $netoDesembolsado = $totalNomina - $totalDeudas;

            NotificarPagoArbitroJob::dispatch($arbitro, $netoDesembolsado, $totalDeudas, $idLotePago);

            return [
                'totalNomina'      => $totalNomina,
                'totalDeudas'      => $totalDeudas,
                'netoDesembolsado' => $netoDesembolsado,
                'idLotePago'       => $idLotePago,
            ];
        });
    }

    /**
     * Reporte financiero para un rango de fechas libre (no hay período fijo
     * obligatorio — mensual/trimestral/anual son solo atajos de UI sobre este
     * mismo filtro). Se basa en el monto registrado de los movimientos no
     * anulados (base contable, no solo lo efectivamente cobrado/pagado).
     *
     * @return array{
     *     totalIngresos: float, totalEgresos: float, neto: float,
     *     porCategoria: Collection<int, array{categoria: string, tipoMovimiento: string, total: float, cantidad: int}>,
     * }
     */
    public function reporte(int $idColegio, string $desde, string $hasta): array
    {
        $movimientos = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
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
        ];
    }

    /**
     * Balance general del colegio: saldo en caja (dinero real que ha
     * entrado/salido — excluye abonos de compensación, que son solo un
     * ajuste contable interno sin movimiento de efectivo) y, por cada
     * árbitro con saldo activo, cuánto le debe el colegio (nómina/externo
     * pendiente) y cuánto debe el árbitro al colegio (mensualidad/multa
     * pendiente).
     *
     * @return array{
     *     saldoEnCaja: float, totalLeDebemos: float, totalNosDeben: float,
     *     porArbitro: Collection<int, array{arbitro: Arbitro, leDebemos: float, nosDebe: float}>,
     * }
     */
    public function balanceGeneral(int $idColegio): array
    {
        $abonosIngreso = AbonoMovimiento::whereHas('movimiento', fn ($q) => $q->where('idColegio', $idColegio)->where('tipoMovimiento', MovimientoFinanciero::TIPO_INGRESO))
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_COMPENSACION_NOMINA)
            ->sum('monto');

        $abonosEgreso = AbonoMovimiento::whereHas('movimiento', fn ($q) => $q->where('idColegio', $idColegio)->where('tipoMovimiento', MovimientoFinanciero::TIPO_EGRESO))
            ->where('anulado', false)
            ->where('metodoPago', '!=', AbonoMovimiento::METODO_COMPENSACION_NOMINA)
            ->sum('monto');

        $porArbitro = Arbitro::where('idColegio', $idColegio)
            ->with('usuario')
            ->get()
            ->map(function (Arbitro $arbitro): array {
                $leDebemos = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                    ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                    ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                    ->get()
                    ->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

                $nosDebe = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                    ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_MULTA])
                    ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                    ->get()
                    ->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

                return ['arbitro' => $arbitro, 'leDebemos' => $leDebemos, 'nosDebe' => $nosDebe];
            })
            ->filter(fn (array $fila) => $fila['leDebemos'] > 0.0 || $fila['nosDebe'] > 0.0)
            ->sortByDesc('leDebemos')
            ->values();

        return [
            'saldoEnCaja'    => (float) $abonosIngreso - (float) $abonosEgreso,
            'totalLeDebemos' => $porArbitro->sum('leDebemos'),
            'totalNosDeben'  => $porArbitro->sum('nosDebe'),
            'porArbitro'     => $porArbitro,
        ];
    }
}
