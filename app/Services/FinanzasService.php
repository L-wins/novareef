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
use Illuminate\Support\Carbon;
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
     *     tipoOrigenMulta?: ?string, idOrigenMulta?: ?int, idLoteCobro?: ?string, observaciones?: ?string,
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
                'idLoteCobro'             => $datos['idLoteCobro'] ?? null,
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
     *     idLotePago?: ?string, observaciones?: ?string}  $datos
     *
     * @throws \RuntimeException  Si el movimiento está anulado o el abono excede el saldo pendiente.
     */
    public function registrarAbono(MovimientoFinanciero $movimiento, array $datos, User $usuario): AbonoMovimiento
    {
        return DB::transaction(function () use ($movimiento, $datos, $usuario): AbonoMovimiento {
            // lockForUpdate + re-consulta (no solo refresh): sin esto, dos
            // abonos concurrentes sobre el mismo movimiento pueden leer el
            // mismo saldoPendiente antes de que ninguno confirme y los dos
            // pasan la validación — el movimiento queda sobre-pagado por un
            // error de carrera, no por la vía sancionada de compensación.
            $movimiento = MovimientoFinanciero::whereKey($movimiento->getKey())->lockForUpdate()->firstOrFail();

            if ($movimiento->estaAnulado()) {
                throw new \RuntimeException('No se puede abonar un movimiento anulado.');
            }

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

            $esCompensacion = $datos['metodoPago'] === AbonoMovimiento::METODO_NOMINA;

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
        DB::transaction(function () use ($movimiento, $usuario, $motivo): void {
            // Mismo motivo que en registrarAbono(): lockForUpdate en vez de
            // operar sobre la instancia ya cargada — evita anular dos veces
            // en paralelo (historial duplicado) o anular justo cuando otro
            // request está registrando un abono sobre el mismo movimiento.
            $movimiento = MovimientoFinanciero::whereKey($movimiento->getKey())->lockForUpdate()->firstOrFail();

            if ($movimiento->estaAnulado()) {
                throw new \RuntimeException('El movimiento ya está anulado.');
            }

            if ($movimiento->abonos()->where('anulado', false)->exists()) {
                throw new \RuntimeException('No se puede anular un movimiento que ya tiene abonos registrados.');
            }

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
     * ingreso_torneo, vinculado al torneo) desde /finanzas/gastos-ingresos
     * cuando el pago llega — ver TorneoController::show() para el resumen de
     * cuánta nómina generada aún no tiene ingreso registrado.
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
     * Anula los egresos de nómina generados automáticamente al finalizar un
     * partido — contraparte de generarMovimientosPorFinalizacionPartido(),
     * invocada por PartidoStateMachine cuando un ejecutivo revierte
     * finalizado → programado. Sin esto, revertir el partido lo dejaba en
     * 'programado' con la deuda de nómina ya generada intacta.
     *
     * Si algún egreso ya tiene abonos (el árbitro ya cobró ese partido), se
     * aborta con RuntimeException sin anular nada — PartidoStateMachine
     * corre esto dentro de la misma transacción de la transición de estado,
     * así que el error revierte también el cambio de estado: no queda un
     * partido "programado" con un pago de nómina ya cobrado colgando.
     *
     * @throws \RuntimeException  Si algún egreso de nómina del partido ya tiene abonos.
     */
    public function anularMovimientosPorReversionPartido(Partido $partido, User $usuario): void
    {
        $movimientos = MovimientoFinanciero::where('idPartido', $partido->idPartido)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->get();

        foreach ($movimientos as $movimiento) {
            if ($movimiento->abonos()->where('anulado', false)->exists()) {
                throw new \RuntimeException(
                    'No se puede revertir: ya se registraron pagos de nómina para este partido. Anula esos pagos primero desde la ficha del árbitro.'
                );
            }
        }

        foreach ($movimientos as $movimiento) {
            $this->anularMovimiento($movimiento, $usuario, "Anulado automáticamente: partido #{$partido->idPartido} revertido de finalizado a programado.");
        }
    }

    /**
     * Registra un movimiento que nace ya resuelto: se crea el movimiento y en
     * la misma transacción se le registra un abono por el monto completo —
     * para los casos donde no existe un estado "pendiente" real porque el
     * dinero ya se movió antes de registrarlo (saldo inicial, gastos/ingresos
     * institucionales). El movimiento resultante queda `estadoMovimiento =
     * pagado`.
     *
     * @param  array{
     *     tipoMovimiento: string, categoria: string, concepto: string,
     *     montoTotal: float|string, fechaMovimiento: string, metodoPago: string,
     *     idArbitro?: ?int, idTorneo?: ?int, observaciones?: ?string,
     * }  $datos
     */
    public function registrarMovimientoPagado(int $idColegio, array $datos, User $usuario): MovimientoFinanciero
    {
        return DB::transaction(function () use ($idColegio, $datos, $usuario): MovimientoFinanciero {
            $movimiento = $this->registrarMovimiento($idColegio, $datos, $usuario);

            $this->registrarAbono($movimiento, [
                'monto'         => $datos['montoTotal'],
                'fechaAbono'    => $datos['fechaMovimiento'],
                'metodoPago'    => $datos['metodoPago'],
                'observaciones' => $datos['observacionesAbono'] ?? null,
            ], $usuario);

            return $movimiento->refresh();
        });
    }

    /**
     * Registra el saldo inicial de apertura del colegio (o un ajuste
     * posterior de caja) como un ingreso categoría `saldo_inicial` con abono
     * automático inmediato por el mismo monto — ya es efectivo real que el
     * colegio tenía antes de usar NovaReef, no una cuenta por cobrar. Nunca
     * se edita el original: una corrección es un nuevo registro.
     *
     * @param  array{monto: float|string, fecha: string, metodoPago: string, observaciones?: ?string}  $datos
     */
    public function registrarSaldoInicial(int $idColegio, array $datos, User $usuario): MovimientoFinanciero
    {
        return $this->registrarMovimientoPagado($idColegio, [
            'tipoMovimiento'       => MovimientoFinanciero::TIPO_INGRESO,
            'categoria'            => MovimientoFinanciero::CATEGORIA_SALDO_INICIAL,
            'concepto'             => 'Saldo inicial de caja',
            'montoTotal'           => $datos['monto'],
            'fechaMovimiento'      => $datos['fecha'],
            'observaciones'        => $datos['observaciones'] ?? null,
            'metodoPago'           => $datos['metodoPago'],
            'observacionesAbono'   => 'Saldo inicial — ya es efectivo disponible, no un cobro pendiente.',
        ], $usuario);
    }

    /** Categorías habilitadas para cobro masivo — ver registrarCobroMasivo(). */
    private const CATEGORIAS_COBRO_MASIVO = [
        MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
        MovimientoFinanciero::CATEGORIA_OTRO_INGRESO,
    ];

    /**
     * Registra el mismo cargo (mensualidad u otro ingreso) a varios árbitros
     * de una sola corrida, agrupados bajo un idLoteCobro común. Por cada
     * árbitro incluido: si ya existe un movimiento no anulado con la misma
     * categoría+concepto en el mismo mes, se omite — permite reintentar una
     * corrida parcial (ej. se cortó a la mitad) sin duplicar a quien ya quedó
     * cobrado. Si el árbitro viene marcado "ya pagó", el movimiento se crea y
     * se salda de inmediato por el monto completo — mismo patrón que
     * registrarSaldoInicial (crear + abonar en la misma transacción). No hay
     * pago parcial dentro del lote: quien necesite abonar parcial usa el
     * flujo individual ya existente después.
     *
     * @param  array{
     *     categoria: string, concepto: string, fechaMovimiento: string,
     *     montoTotal: float|string, observaciones?: ?string,
     *     cargos: array<int, array{
     *         idArbitro: int, incluir?: bool, monto?: float|string|null,
     *         yaPago?: bool, metodoPago?: ?string, fechaAbono?: ?string,
     *     }>,
     * }  $datos
     *
     * @return array{
     *     idLoteCobro: string, idLotePago: string,
     *     creados: Collection<int, MovimientoFinanciero>,
     *     omitidos: Collection<int, array{idArbitro: int, motivo: string}>,
     *     totalCreados: int, totalPagados: int, totalOmitidos: int,
     * }
     *
     * @throws \RuntimeException  Si la categoría no está habilitada para cobro masivo o no hay ningún cargo incluido.
     */
    public function registrarCobroMasivo(int $idColegio, array $datos, User $usuario): array
    {
        $categoria = $datos['categoria'];
        if (! in_array($categoria, self::CATEGORIAS_COBRO_MASIVO, true)) {
            throw new \RuntimeException("La categoría «{$categoria}» no está habilitada para cobro masivo.");
        }

        $incluidos = array_values(array_filter($datos['cargos'], fn (array $c) => ! empty($c['incluir'])));
        if ($incluidos === []) {
            throw new \RuntimeException('Selecciona al menos un árbitro para el cobro.');
        }

        return DB::transaction(function () use ($idColegio, $datos, $categoria, $incluidos, $usuario): array {
            $idLoteCobro = (string) Str::uuid();
            // Distinto de idLoteCobro (agrupa los MOVIMIENTOS de esta corrida)
            // — idLotePago agrupa los ABONOS de los "ya pagó" de esta misma
            // corrida, para que generen un comprobante descargable igual que
            // un pago de nómina (ver datosComprobanteCobro()).
            $idLotePago = (string) Str::uuid();

            // Una sola query resuelve pertenencia al colegio + estado del
            // árbitro — evita N queries y sirve de guardia de tenant a la vez.
            $idsSolicitados  = array_column($incluidos, 'idArbitro');
            $arbitrosValidos = Arbitro::where('idColegio', $idColegio)
                ->whereIn('idArbitro', $idsSolicitados)
                ->whereNotIn('estadoArbitro', ['retirado'])
                ->pluck('idArbitro')
                ->flip();

            $creados  = collect();
            $omitidos = collect();

            foreach ($incluidos as $cargo) {
                $idArbitro = (int) $cargo['idArbitro'];

                if (! $arbitrosValidos->has($idArbitro)) {
                    $omitidos->push(['idArbitro' => $idArbitro, 'motivo' => 'Árbitro no pertenece al colegio activo o está retirado.']);
                    continue;
                }

                $fecha = $datos['fechaMovimiento'];
                $monto = (float) ($cargo['monto'] ?? $datos['montoTotal']);

                $datosAbono = ! empty($cargo['yaPago']) ? [
                    'fechaAbono'  => $cargo['fechaAbono'] ?? $fecha,
                    'metodoPago'  => $cargo['metodoPago'],
                    'idLotePago'  => $idLotePago,
                ] : null;

                $resultado = $this->procesarCargoIndividual(
                    $idColegio, $idArbitro, $categoria, $datos['concepto'],
                    $monto, $fecha, $idLoteCobro, $datosAbono, $usuario,
                );

                if ($resultado['movimiento'] === null) {
                    $omitidos->push(['idArbitro' => $idArbitro, 'motivo' => $resultado['omitido']]);
                    continue;
                }

                $creados->push($resultado['movimiento']);
            }

            return [
                'idLoteCobro'   => $idLoteCobro,
                'idLotePago'    => $idLotePago,
                'creados'       => $creados,
                'omitidos'      => $omitidos,
                'totalCreados'  => $creados->count(),
                'totalPagados'  => $creados->filter(fn (MovimientoFinanciero $m) => $m->estadoMovimiento === MovimientoFinanciero::ESTADO_PAGADO)->count(),
                'totalOmitidos' => $omitidos->count(),
            ];
        });
    }

    /**
     * Cargo individual reutilizado por registrarCobroMasivo() (cobro manual
     * disparado por un tesorero) y generarCuotaMensualAutomatica() (Job
     * automático sin usuario detrás): valida duplicado por categoría+árbitro
     * dentro del mismo mes calendario, crea el movimiento y opcionalmente lo
     * salda de inmediato si $datosAbono viene informado.
     *
     * @return array{movimiento: ?MovimientoFinanciero, omitido: ?string}
     */
    private function procesarCargoIndividual(
        int $idColegio,
        int $idArbitro,
        string $categoria,
        string $concepto,
        float $monto,
        string $fecha,
        ?string $idLoteCobro,
        ?array $datosAbono,
        ?User $usuario,
    ): array {
        $inicioMes = Carbon::parse($fecha)->startOfMonth()->toDateString();
        $finMes    = Carbon::parse($fecha)->endOfMonth()->toDateString();

        // Duplicado = misma categoría + mismo árbitro + mismo mes, sin
        // importar el texto exacto del concepto — comparar por concepto
        // literal dejaba pasar dobles cobros con el mismo significado
        // escrito distinto (ej. "Mensualidad julio" vs "Mensualidad Julio 2026").
        $yaExiste = MovimientoFinanciero::where('idColegio', $idColegio)
            ->where('idArbitro', $idArbitro)
            ->where('categoria', $categoria)
            ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
            ->whereBetween('fechaMovimiento', [$inicioMes, $finMes])
            ->exists();

        if ($yaExiste) {
            return ['movimiento' => null, 'omitido' => 'Ya existe un cobro de este concepto en el mismo mes.'];
        }

        $movimiento = $this->registrarMovimiento($idColegio, [
            'tipoMovimiento'  => MovimientoFinanciero::TIPO_INGRESO,
            'categoria'       => $categoria,
            'concepto'        => $concepto,
            'montoTotal'      => $monto,
            'fechaMovimiento' => $fecha,
            'idArbitro'       => $idArbitro,
            'idLoteCobro'     => $idLoteCobro,
        ], $usuario);

        if ($datosAbono !== null) {
            if ($usuario === null) {
                throw new \LogicException('No se puede registrar un abono sin un usuario responsable.');
            }

            $this->registrarAbono($movimiento, ['monto' => $monto, ...$datosAbono], $usuario);
            $movimiento->refresh();
        }

        return ['movimiento' => $movimiento, 'omitido' => null];
    }

    /**
     * Genera el cargo de mensualidad del mes vigente para un árbitro —
     * invocado por GenerarCuotasMensualesJob, sin usuario detrás (mismo
     * criterio que generarMovimientosPorFinalizacionPartido). Nace
     * `pendiente` y nunca se autoabona: el cobro real lo registra el
     * tesorero después, con un abono individual o desde Cobro Masivo.
     * Reusa la misma deduplicación mensual de registrarCobroMasivo() — si el
     * job corre más de una vez tras el vencimiento (ej. una caída), no
     * duplica el cargo.
     */
    public function generarCuotaMensualAutomatica(int $idColegio, Arbitro $arbitro, float $monto, string $fecha, string $idLoteCobro): ?MovimientoFinanciero
    {
        $resultado = $this->procesarCargoIndividual(
            $idColegio,
            $arbitro->idArbitro,
            MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
            'Mensualidad — ' . Carbon::parse($fecha)->translatedFormat('F Y'),
            $monto,
            $fecha,
            $idLoteCobro,
            null,
            null,
        );

        return $resultado['movimiento'];
    }

    /**
     * Pago en efectivo/transferencia de uno o varios egresos de nómina
     * pendientes del árbitro, cada uno por su saldo completo — desde la
     * ficha financiera, ya sea uno a la vez o varios en un solo lote.
     * Reemplaza la vieja vista de "pago acumulado"; a diferencia de esa, ya
     * no neta deudas (eso ahora es compensarDeudaConNomina(), una acción
     * aparte por deuda).
     *
     * @param  int[]  $idsMovimientos  Egresos nomina_arbitro/arbitro_externo pendientes a pagar.
     * @param  array{fecha: string, metodoPago: string}  $datosPago
     *
     * @return array{total: float, idLotePago: string}
     *
     * @throws \RuntimeException  Si no queda ningún movimiento pendiente entre los seleccionados.
     */
    public function pagarNominaArbitro(Arbitro $arbitro, array $idsMovimientos, array $datosPago, User $usuario): array
    {
        return DB::transaction(function () use ($arbitro, $idsMovimientos, $datosPago, $usuario): array {
            $movimientos = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                ->whereIn('idMovimiento', $idsMovimientos)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                ->lockForUpdate()
                ->get();

            if ($movimientos->isEmpty()) {
                throw new \RuntimeException('Selecciona al menos un pago de nómina pendiente.');
            }

            $idLotePago = (string) Str::uuid();
            $total      = 0.0;

            foreach ($movimientos as $movimiento) {
                $saldo = $movimiento->saldoPendiente();
                if ($saldo <= 0.0) {
                    continue;
                }

                $this->registrarAbono($movimiento, [
                    'monto'      => $saldo,
                    'fechaAbono' => $datosPago['fecha'],
                    'metodoPago' => $datosPago['metodoPago'],
                    'idLotePago' => $idLotePago,
                ], $usuario);

                $total += $saldo;
            }

            if ($total <= 0.0) {
                throw new \RuntimeException('Los movimientos seleccionados ya estaban pagados.');
            }

            NotificarPagoArbitroJob::dispatch($arbitro, $total, 0.0, $idLotePago);

            return ['total' => $total, 'idLotePago' => $idLotePago];
        });
    }

    /**
     * Compensa una deuda (mensualidad/multa) del árbitro contra su nómina
     * pendiente, hasta donde alcance — nunca lanza error por falta de
     * nómina, simplemente compensa lo que hay disponible ahora mismo y deja
     * el resto de la deuda pendiente (queda `parcial`, se puede volver a
     * compensar cuando haya más nómina, o cobrar el resto en efectivo con
     * un abono normal). Es la acción explícita que reemplaza el neteo
     * automático de la vieja vista de "pago acumulado" — el árbitro puede
     * quedar con más "nos debe" que "le debemos" mientras tanto, eso ya se
     * refleja en su estado de cuenta sin necesidad de un campo aparte.
     *
     * @return array{montoCompensado: float, idLotePago: string}
     *
     * @throws \RuntimeException  Si la deuda ya está saldada o no hay nada de nómina pendiente para compensar.
     */
    public function compensarDeudaConNomina(Arbitro $arbitro, MovimientoFinanciero $deuda, User $usuario): array
    {
        return DB::transaction(function () use ($arbitro, $deuda, $usuario): array {
            $deuda->refresh();

            if ($deuda->idArbitro !== $arbitro->idArbitro || ! in_array($deuda->categoria, [MovimientoFinanciero::CATEGORIA_MENSUALIDAD, MovimientoFinanciero::CATEGORIA_MULTA], true)) {
                throw new \RuntimeException('Ese movimiento no es una deuda compensable de este árbitro.');
            }

            $saldoDeuda = $deuda->saldoPendiente();
            if ($saldoDeuda <= 0.0) {
                throw new \RuntimeException('Esta deuda ya está saldada.');
            }

            $movimientosNomina = MovimientoFinanciero::where('idArbitro', $arbitro->idArbitro)
                ->whereIn('categoria', [MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO, MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO])
                ->where('estadoMovimiento', '!=', MovimientoFinanciero::ESTADO_ANULADO)
                ->lockForUpdate()
                ->orderBy('fechaMovimiento')
                ->get()
                ->filter(fn (MovimientoFinanciero $m) => $m->saldoPendiente() > 0.0);

            $disponible = $movimientosNomina->sum(fn (MovimientoFinanciero $m) => $m->saldoPendiente());
            if ($disponible <= 0.0) {
                throw new \RuntimeException('El árbitro no tiene nómina pendiente para compensar.');
            }

            $monto      = min($saldoDeuda, $disponible);
            $idLotePago = (string) Str::uuid();
            $fecha      = today()->format('Y-m-d');

            $this->registrarAbono($deuda, [
                'monto'         => $monto,
                'fechaAbono'    => $fecha,
                'metodoPago'    => AbonoMovimiento::METODO_NOMINA,
                'idLotePago'    => $idLotePago,
                'observaciones' => 'Compensado contra nómina pendiente del árbitro',
            ], $usuario);

            $restante = $monto;
            foreach ($movimientosNomina as $movimiento) {
                if ($restante <= 0.0) {
                    break;
                }

                $saldo         = $movimiento->saldoPendiente();
                $montoAbonado  = min($restante, $saldo);

                $this->registrarAbono($movimiento, [
                    'monto'         => $montoAbonado,
                    'fechaAbono'    => $fecha,
                    'metodoPago'    => AbonoMovimiento::METODO_NOMINA,
                    'idLotePago'    => $idLotePago,
                    'observaciones' => "Compensado contra deuda #{$deuda->idMovimiento} del árbitro",
                ], $usuario);

                $restante -= $montoAbonado;
            }

            NotificarPagoArbitroJob::dispatch($arbitro, 0.0, $monto, $idLotePago);

            return ['montoCompensado' => $monto, 'idLotePago' => $idLotePago];
        });
    }

    // Las consultas de lectura/agregación (reporte, serie mensual, balance,
    // resumen del listado) viven en ReporteFinanzasService.
}
