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
     * confirmada (valor resuelto vía DesignacionService::calcularPago) — la
     * deuda del colegio hacia cada árbitro se acumula de inmediato, visible
     * desde su perfil, sin depender de nada más.
     *
     * El ingreso del torneo NO se genera aquí: el colegio no recibe ese
     * dinero al finalizar el partido, sino cuando el organizador
     * efectivamente paga. Ese ingreso se registra manualmente (categoría
     * ingreso_torneo, vinculado al torneo) desde /finanzas/crear cuando el
     * pago llega — ver TorneoController::show() para el resumen de cuánta
     * nómina generada aún no tiene ingreso registrado.
     *
     * En modalidad 'campo' no genera nada: el árbitro cobra en efectivo
     * directo del organizador y el colegio no gestiona ese dinero.
     *
     * Idempotente — si ya existen egresos de nómina para este partido (el
     * job que la invoca puede reintentarse), no vuelve a generarlos.
     */
    public function generarMovimientosPorFinalizacionPartido(Partido $partido): void
    {
        if ($partido->modalidadPago !== 'nomina') {
            return;
        }

        $yaGenerado = MovimientoFinanciero::where('idPartido', $partido->idPartido)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO)
            ->exists();

        if ($yaGenerado) {
            return;
        }

        $designacionesConfirmadas = $partido->designacionesConfirmadas()->with('rol')->get();

        DB::transaction(function () use ($partido, $designacionesConfirmadas): void {
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
            }
        });
    }

    /**
     * Registra el saldo inicial de apertura del colegio (o un ajuste
     * posterior de caja) como un ingreso categoría `saldo_inicial` con abono
     * automático inmediato por el mismo monto — ya es efectivo real que el
     * colegio tenía antes de usar NovaReef, no una cuenta por cobrar. Nunca
     * se edita el original: una corrección es un nuevo registro.
     *
     * @param  array{monto: float|string, fecha: string, observaciones?: ?string}  $datos
     */
    public function registrarSaldoInicial(int $idColegio, array $datos, User $usuario): MovimientoFinanciero
    {
        return DB::transaction(function () use ($idColegio, $datos, $usuario): MovimientoFinanciero {
            $movimiento = $this->registrarMovimiento($idColegio, [
                'tipoMovimiento'  => MovimientoFinanciero::TIPO_INGRESO,
                'categoria'       => MovimientoFinanciero::CATEGORIA_SALDO_INICIAL,
                'concepto'        => 'Saldo inicial de caja',
                'montoTotal'      => $datos['monto'],
                'fechaMovimiento' => $datos['fecha'],
                'observaciones'   => $datos['observaciones'] ?? null,
            ], $usuario);

            $this->registrarAbono($movimiento, [
                'monto'         => $datos['monto'],
                'fechaAbono'    => $datos['fecha'],
                'metodoPago'    => AbonoMovimiento::METODO_OTRO,
                'observaciones' => 'Saldo inicial — ya es efectivo disponible, no un cobro pendiente.',
            ], $usuario);

            return $movimiento->refresh();
        });
    }

    /** Categorías de egreso que representan pago a un árbitro por su labor. */
    private const CATEGORIAS_PAGO_ARBITRO = [
        MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
        MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO,
    ];

    /**
     * Egresos de nómina/externo del árbitro aún no saldados por completo,
     * con el partido y torneo de origen — es el desglose que el estado de
     * cuenta y el badge del perfil necesitan para no repetir la query.
     *
     * @return Collection<int, MovimientoFinanciero>
     */
    private function egresosPendientesArbitro(Arbitro $arbitro): Collection
    {
        return MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
            ->whereIn('categoria', self::CATEGORIAS_PAGO_ARBITRO)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->with(['partido', 'torneo'])
            ->conTotalAbonado()
            ->get()
            ->filter(fn (MovimientoFinanciero $m) => $m->saldoPendiente() > 0.0)
            ->sortBy('fechaMovimiento')
            ->values();
    }

    /** Saldo total pendiente por cobrar del árbitro — usado por el badge del perfil. */
    public function saldoPendienteArbitro(Arbitro $arbitro): float
    {
        return $this->egresosPendientesArbitro($arbitro)->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());
    }

    /**
     * Estado de cuenta del árbitro: saldo pendiente por cobrar (con el
     * detalle partido por partido), historial de pagos recibidos, historial
     * de multas y descuentos aplicados en nómina (compensación automática de
     * deudas al momento del pago acumulado).
     *
     * @return array{
     *     saldoPendienteCobrar: float,
     *     pendientesPorPartido: Collection<int, MovimientoFinanciero>,
     *     historialPagos: Collection<int, AbonoMovimiento>,
     *     historialMultas: Collection<int, MovimientoFinanciero>,
     *     descuentosNomina: Collection<int, AbonoMovimiento>,
     * }
     */
    public function estadoCuentaArbitro(Arbitro $arbitro): array
    {
        $pendientesPorPartido = $this->egresosPendientesArbitro($arbitro);
        $saldoPendienteCobrar = (float) $pendientesPorPartido->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());

        $historialPagos = AbonoMovimiento::whereHas('movimiento', function ($q) use ($arbitro): void {
                $q->where('idArbitro', $arbitro->idArbitro)->whereIn('categoria', self::CATEGORIAS_PAGO_ARBITRO);
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
            'pendientesPorPartido' => $pendientesPorPartido,
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

    // Las consultas de lectura/agregación (reporte, serie mensual, balance,
    // resumen del listado) viven en ReporteFinanzasService.
}
