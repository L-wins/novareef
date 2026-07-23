<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Arbitro;
use App\Models\AsistenciaAcademica;
use App\Models\CategoriaArbitro;
use App\Models\Colegio;
use App\Models\MovimientoFinanciero;
use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Models\User;
use App\Services\AsistenciaAcademicaService;
use App\Services\FinanzasService;
use App\Services\JustificacionAcademicaService;
use App\Services\SesionAcademicaService;
use App\Services\TipoSesionAcademicaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Siembra datos de prueba realistas para Finanzas (M06) y Académico (M08) en
 * ASOCAFA — a diferencia de SembrarDatosCargaCommand (volumen para pruebas de
 * carga en 6 colegios), este comando es de un solo colegio ya existente y
 * cubre variedad de estados/casos de borde de estos dos módulos puntuales
 * para poder navegarlos con datos reales sin depender de crearlos a mano
 * desde el panel. Usa los Services reales (FinanzasService,
 * SesionAcademicaService, etc.) — mismo criterio que el otro comando: nunca
 * saltarse las reglas de negocio (recalculo de estadoMovimiento, generación
 * automática de asistencia 'ausente', deadline de justificación...).
 *
 * No es idempotente a propósito — está pensado para correr una sola vez
 * sobre un ASOCAFA sin datos de estos dos módulos todavía.
 */
class SembrarFinanzasAcademicoAsocafaCommand extends Command
{
    protected $signature = 'novareef:sembrar-finanzas-academico-asocafa';

    protected $description = 'Siembra movimientos financieros y sesiones académicas de prueba en el colegio ASOCAFA';

    public function handle(
        FinanzasService $finanzas,
        SesionAcademicaService $sesiones,
        AsistenciaAcademicaService $asistencias,
        JustificacionAcademicaService $justificaciones,
        TipoSesionAcademicaService $tiposSesion,
    ): int {
        mt_srand(20260723);

        // Mismo aislamiento que SembrarDatosCargaCommand: sin correo real ni
        // colas encoladas contra la BD real.
        Mail::fake();
        Queue::fake();

        $asocafa = Colegio::where('codigoColegio', 'ASOCAFA')->orWhere('nombreColegio', 'ASOCAFA')->first();

        if (! $asocafa) {
            $this->error('No se encontró el colegio ASOCAFA.');

            return self::FAILURE;
        }

        $idColegio = $asocafa->idColegio;

        $ejecutivo = User::where('idColegio', $idColegio)->where('rolUsuario', 'ejecutivo')->firstOrFail();
        $tesorero = User::where('idColegio', $idColegio)->where('rolUsuario', 'tesorero')->firstOrFail();
        $tecnico = User::where('idColegio', $idColegio)->where('rolUsuario', 'tecnico')->firstOrFail();
        $sancionesUsuario = User::where('idColegio', $idColegio)->where('rolUsuario', 'sanciones')->firstOrFail();

        $this->info('=== Sembrando Finanzas y Académico en ASOCAFA ===');

        $this->sembrarFinanzas($finanzas, $idColegio, $ejecutivo, $tesorero);
        $this->sembrarAcademico($sesiones, $asistencias, $justificaciones, $tiposSesion, $idColegio, $tecnico, $sancionesUsuario);

        $this->info('=== Listo ===');

        return self::SUCCESS;
    }

    // ── Finanzas (M06) ──────────────────────

    private function sembrarFinanzas(FinanzasService $finanzas, int $idColegio, User $ejecutivo, User $tesorero): void
    {
        $this->info("\n--- Finanzas ---");

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->inRandomOrder()
            ->limit(40)
            ->get();

        if ($arbitros->count() < 10) {
            $this->warn('Muy pocos árbitros activos en ASOCAFA — se omite la siembra de finanzas.');

            return;
        }

        $finanzas->registrarSaldoInicial($idColegio, [
            'monto' => 15_000_000,
            'fecha' => now()->subMonths(7)->startOfMonth()->toDateString(),
            'metodoPago' => 'pago_digital',
            'observaciones' => 'Saldo inicial de caja (dato de prueba).',
        ], $ejecutivo);
        $this->line('Saldo inicial registrado.');

        // Mensualidades: 35 árbitros x 6 meses, con mezcla de estados.
        $poolMensualidad = $arbitros->take(35);
        $creadasMensualidad = 0;

        foreach (range(5, 0) as $mesesAtras) {
            $fecha = now()->subMonths($mesesAtras)->startOfMonth()->addDays(mt_rand(0, 4))->toDateString();

            foreach ($poolMensualidad as $arbitro) {
                $movimiento = $finanzas->registrarMovimiento($idColegio, [
                    'tipoMovimiento' => MovimientoFinanciero::TIPO_INGRESO,
                    'categoria' => MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
                    'concepto' => 'Mensualidad — ' . ucfirst(mb_strtolower(now()->subMonths($mesesAtras)->translatedFormat('F Y'))),
                    'montoTotal' => 50000,
                    'fechaMovimiento' => $fecha,
                    'idArbitro' => $arbitro->idArbitro,
                ], $tesorero);

                $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha);
                $creadasMensualidad++;
            }
        }
        $this->line("Mensualidades creadas: {$creadasMensualidad}.");

        // Multas económicas (origen manual — sin enganche real a Sanciones).
        $montosMulta = [20000, 30000, 50000, 80000];
        foreach (range(1, 15) as $i) {
            $arbitro = $arbitros->random();
            $fecha = now()->subDays(mt_rand(5, 200))->toDateString();

            $movimiento = $finanzas->registrarMovimiento($idColegio, [
                'tipoMovimiento' => MovimientoFinanciero::TIPO_INGRESO,
                'categoria' => MovimientoFinanciero::CATEGORIA_MULTA,
                'concepto' => 'Multa disciplinaria (dato de prueba)',
                'montoTotal' => $montosMulta[array_rand($montosMulta)],
                'fechaMovimiento' => $fecha,
                'idArbitro' => $arbitro->idArbitro,
                'tipoOrigenMulta' => 'manual',
            ], $tesorero);

            $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha);
        }
        $this->line('Multas creadas: 15.');

        // Ingresos de torneo y otros ingresos.
        foreach (range(1, 5) as $i) {
            $fecha = now()->subDays(mt_rand(5, 210))->toDateString();
            $movimiento = $finanzas->registrarMovimiento($idColegio, [
                'tipoMovimiento' => MovimientoFinanciero::TIPO_INGRESO,
                'categoria' => MovimientoFinanciero::CATEGORIA_INGRESO_TORNEO,
                'concepto' => 'Pago de organizador — Liga ' . now()->subDays(mt_rand(5, 210))->year . ' (dato de prueba)',
                'montoTotal' => mt_rand(800000, 3000000),
                'fechaMovimiento' => $fecha,
            ], $tesorero);
            $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha, probabilidadPagado: 85);
        }

        foreach (range(1, 4) as $i) {
            $fecha = now()->subDays(mt_rand(5, 180))->toDateString();
            $movimiento = $finanzas->registrarMovimiento($idColegio, [
                'tipoMovimiento' => MovimientoFinanciero::TIPO_INGRESO,
                'categoria' => MovimientoFinanciero::CATEGORIA_OTRO_INGRESO,
                'concepto' => 'Venta de material arbitral (dato de prueba)',
                'montoTotal' => mt_rand(50000, 300000),
                'fechaMovimiento' => $fecha,
            ], $tesorero);
            $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha);
        }
        $this->line('Ingresos de torneo y otros ingresos creados.');

        // Egresos: nómina de árbitro (sin partido real detrás — dato de prueba).
        $poolNomina = $arbitros->take(40);
        $montosNomina = [40000, 55000, 70000, 90000];
        $movimientosNomina = collect();
        foreach ($poolNomina as $arbitro) {
            $fecha = now()->subDays(mt_rand(1, 200))->toDateString();
            $movimiento = $finanzas->registrarMovimiento($idColegio, [
                'tipoMovimiento' => MovimientoFinanciero::TIPO_EGRESO,
                'categoria' => MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
                'concepto' => 'Nómina — partido de prueba',
                'montoTotal' => $montosNomina[array_rand($montosNomina)],
                'fechaMovimiento' => $fecha,
                'idArbitro' => $arbitro->idArbitro,
            ], null);
            $movimientosNomina->push($movimiento);
            $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha);
        }
        $this->line('Egresos de nómina creados: ' . $movimientosNomina->count());

        foreach (range(1, 6) as $i) {
            $fecha = now()->subDays(mt_rand(5, 150))->toDateString();
            $movimiento = $finanzas->registrarMovimiento($idColegio, [
                'tipoMovimiento' => MovimientoFinanciero::TIPO_EGRESO,
                'categoria' => MovimientoFinanciero::CATEGORIA_ARBITRO_EXTERNO,
                'concepto' => 'Árbitro externo de refuerzo (dato de prueba)',
                'montoTotal' => mt_rand(60000, 120000),
                'fechaMovimiento' => $fecha,
                'nombreArbitroExterno' => 'Árbitro Invitado ' . $i,
                'documentoArbitroExterno' => (string) mt_rand(10000000, 99999999),
            ], $tesorero);
            $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha, probabilidadPagado: 90);
        }

        $conceptosGasto = [
            MovimientoFinanciero::CATEGORIA_GASTO_FIJO => ['Arriendo de oficina', 'Nómina administrativa'],
            MovimientoFinanciero::CATEGORIA_GASTO_INSTITUCIONAL => ['Afiliación FCF', 'Capacitación institucional'],
            MovimientoFinanciero::CATEGORIA_GASTO_VARIO => ['Papelería', 'Transporte logístico'],
        ];
        foreach ($conceptosGasto as $categoria => $conceptos) {
            foreach ($conceptos as $concepto) {
                foreach (range(1, 3) as $i) {
                    $fecha = now()->subDays(mt_rand(5, 200))->toDateString();
                    $movimiento = $finanzas->registrarMovimiento($idColegio, [
                        'tipoMovimiento' => MovimientoFinanciero::TIPO_EGRESO,
                        'categoria' => $categoria,
                        'concepto' => "{$concepto} (dato de prueba)",
                        'montoTotal' => mt_rand(50000, 500000),
                        'fechaMovimiento' => $fecha,
                    ], $tesorero);
                    $this->resolverEstadoAleatorio($finanzas, $movimiento, $tesorero, $fecha);
                }
            }
        }
        $this->line('Egresos institucionales/varios creados.');

        // Compensación de deuda contra nómina — ejercita compensarDeudaConNomina().
        $arbitroCompensacion = $arbitros->last();
        $deuda = $finanzas->registrarMovimiento($idColegio, [
            'tipoMovimiento' => MovimientoFinanciero::TIPO_INGRESO,
            'categoria' => MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
            'concepto' => 'Mensualidad pendiente para compensar (dato de prueba)',
            'montoTotal' => 50000,
            'fechaMovimiento' => now()->subDays(20)->toDateString(),
            'idArbitro' => $arbitroCompensacion->idArbitro,
        ], $tesorero);
        $finanzas->registrarMovimiento($idColegio, [
            'tipoMovimiento' => MovimientoFinanciero::TIPO_EGRESO,
            'categoria' => MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO,
            'concepto' => 'Nómina — partido de prueba (para compensación)',
            'montoTotal' => 70000,
            'fechaMovimiento' => now()->subDays(18)->toDateString(),
            'idArbitro' => $arbitroCompensacion->idArbitro,
        ], null);
        $finanzas->compensarDeudaConNomina($arbitroCompensacion, $deuda, $tesorero);
        $this->line('Compensación deuda-nómina ejecutada para 1 árbitro.');

        // Pago acumulado en lote — ejercita pagarNominaArbitro().
        $arbitroLote = $poolNomina->first();
        $pendientesLote = MovimientoFinanciero::where('idArbitro', $arbitroLote->idArbitro)
            ->where('categoria', MovimientoFinanciero::CATEGORIA_NOMINA_ARBITRO)
            ->where('estadoMovimiento', MovimientoFinanciero::ESTADO_PENDIENTE)
            ->pluck('idMovimiento');

        if ($pendientesLote->isNotEmpty()) {
            $finanzas->pagarNominaArbitro($arbitroLote, $pendientesLote->all(), [
                'fecha' => now()->toDateString(),
                'metodoPago' => 'pago_digital',
            ], $tesorero);
            $this->line('Pago acumulado en lote ejecutado para 1 árbitro (' . $pendientesLote->count() . ' movimientos).');
        }
    }

    /**
     * Deja el movimiento recién creado en un estado aleatorio realista:
     * pagado / parcial / pendiente / anulado (el anulado se resuelve antes
     * que cualquier abono, porque FinanzasService no permite anular un
     * movimiento con abonos previos).
     */
    private function resolverEstadoAleatorio(FinanzasService $finanzas, MovimientoFinanciero $movimiento, User $usuario, string $fecha, int $probabilidadPagado = 55): void
    {
        $tirada = mt_rand(1, 100);

        if ($tirada <= 5) {
            $finanzas->anularMovimiento($movimiento, $usuario, 'Registro duplicado (dato de prueba).');

            return;
        }

        $metodo = mt_rand(0, 1) ? 'efectivo' : 'pago_digital';

        if ($tirada <= 5 + $probabilidadPagado) {
            $finanzas->registrarAbono($movimiento, [
                'monto' => $movimiento->montoTotal,
                'fechaAbono' => $fecha,
                'metodoPago' => $metodo,
            ], $usuario);

            return;
        }

        if ($tirada <= 5 + $probabilidadPagado + 20) {
            $finanzas->registrarAbono($movimiento, [
                'monto' => round(((float) $movimiento->montoTotal) * 0.5, 2),
                'fechaAbono' => $fecha,
                'metodoPago' => $metodo,
            ], $usuario);

            return;
        }

        // El resto queda pendiente, sin tocar.
    }

    // ── Académico (M08) ─────────────────────

    private function sembrarAcademico(
        SesionAcademicaService $sesiones,
        AsistenciaAcademicaService $asistencias,
        JustificacionAcademicaService $justificaciones,
        TipoSesionAcademicaService $tiposSesion,
        int $idColegio,
        User $tecnico,
        User $sancionesUsuario,
    ): void {
        $this->info("\n--- Académico ---");

        $tipos = [
            $tiposSesion->crear($idColegio, ['etiqueta' => 'Capacitación FCF', 'esOficial' => true, 'descripcion' => 'Prueba oficial de la Federación Colombiana de Fútbol.']),
            $tiposSesion->crear($idColegio, ['etiqueta' => 'Charla técnica', 'esOficial' => false, 'descripcion' => 'Actualización de reglas de juego.']),
            $tiposSesion->crear($idColegio, ['etiqueta' => 'Actualización reglamentaria', 'esOficial' => false, 'descripcion' => null]),
        ];
        $this->line('Tipos de sesión creados: ' . count($tipos));

        $categorias = CategoriaArbitro::where('idColegio', $idColegio)->pluck('idCategoria')->all();

        // 14 sesiones históricas finalizadas, ~2 por mes en los últimos 7 meses.
        $temas = ['Interpretación del fuera de lugar', 'Manejo de tarjetas y disciplina', 'Protocolo de arbitraje VAR', 'Condición física del árbitro', 'Actualización IFAB'];
        $finalizadas = 0;
        foreach (range(6, 0) as $mesesAtras) {
            foreach ([1, 2] as $numeroDelMes) {
                $dirigidaACategoria = mt_rand(0, 2) === 0 && ! empty($categorias);

                $sesion = $sesiones->crearSesion($idColegio, [
                    'idTipoSesion' => $tipos[array_rand($tipos)]->idTipoSesion,
                    'modalidad' => mt_rand(0, 1) ? SesionAcademica::MODALIDAD_PRESENCIAL : SesionAcademica::MODALIDAD_VIRTUAL,
                    'urlVirtual' => 'https://meet.test.com/asocafa-' . mt_rand(1000, 9999),
                    'tema' => $temas[array_rand($temas)],
                    'descripcion' => 'Sesión de capacitación (dato de prueba).',
                    'lugar' => 'Sede administrativa ASOCAFA',
                    'fechaSesion' => now()->subMonths($mesesAtras)->startOfMonth()->addDays(($numeroDelMes - 1) * 14 + mt_rand(0, 5))->toDateString(),
                    'horaSesion' => sprintf('%02d:00', mt_rand(8, 18)),
                    'duracionMinutos' => [60, 90, 120][array_rand([60, 90, 120])],
                    'dirigidaA' => $dirigidaACategoria ? SesionAcademica::DIRIGIDA_CATEGORIA : SesionAcademica::DIRIGIDA_TODOS,
                    'idCategoria' => $dirigidaACategoria ? $categorias[array_rand($categorias)] : null,
                    'modoAsistencia' => mt_rand(0, 1) ? SesionAcademica::MODO_MANUAL : SesionAcademica::MODO_SCANNER,
                ], $tecnico);

                $sesiones->abrirSesion($sesion);

                // ~80% de asistencia real.
                foreach ($sesion->asistencias as $asistencia) {
                    if (mt_rand(1, 100) <= 80) {
                        $asistencias->corregirMarca($asistencia, AsistenciaAcademica::ESTADO_PRESENTE);
                    }
                }

                $sesiones->confirmarYCerrarSesion($sesion->fresh());
                $finalizadas++;
            }
        }
        $this->line("Sesiones finalizadas creadas: {$finalizadas}.");

        // 2 sesiones canceladas.
        foreach (range(1, 2) as $i) {
            $sesion = $sesiones->crearSesion($idColegio, [
                'idTipoSesion' => $tipos[array_rand($tipos)]->idTipoSesion,
                'modalidad' => SesionAcademica::MODALIDAD_PRESENCIAL,
                'tema' => 'Sesión cancelada (dato de prueba)',
                'lugar' => 'Sede administrativa ASOCAFA',
                'fechaSesion' => now()->subDays(mt_rand(10, 90))->toDateString(),
                'horaSesion' => '09:00',
                'duracionMinutos' => 60,
                'dirigidaA' => SesionAcademica::DIRIGIDA_TODOS,
                'modoAsistencia' => SesionAcademica::MODO_MANUAL,
            ], $tecnico);
            $sesiones->cancelarSesion($sesion);
        }
        $this->line('Sesiones canceladas creadas: 2.');

        // 1 sesión próxima, aún programada (sin abrir).
        $sesiones->crearSesion($idColegio, [
            'idTipoSesion' => $tipos[0]->idTipoSesion,
            'modalidad' => SesionAcademica::MODALIDAD_PRESENCIAL,
            'tema' => 'Capacitación FCF — próxima jornada',
            'lugar' => 'Sede administrativa ASOCAFA',
            'fechaSesion' => now()->addDays(mt_rand(5, 20))->toDateString(),
            'horaSesion' => '08:00',
            'duracionMinutos' => 120,
            'dirigidaA' => SesionAcademica::DIRIGIDA_TODOS,
            'modoAsistencia' => SesionAcademica::MODO_MANUAL,
        ], $tecnico);
        $this->line('Sesión próxima (programada) creada: 1.');

        // 2 sesiones recientes con justificaciones (pendiente/aprobada/rechazada) —
        // el plazo de justificación (fechaSesion + 3 días) todavía no vence.
        foreach ([1, 2] as $diasAtras) {
            $sesion = $sesiones->crearSesion($idColegio, [
                'idTipoSesion' => $tipos[array_rand($tipos)]->idTipoSesion,
                'modalidad' => SesionAcademica::MODALIDAD_PRESENCIAL,
                'tema' => 'Sesión reciente con justificaciones (dato de prueba)',
                'lugar' => 'Sede administrativa ASOCAFA',
                'fechaSesion' => now()->subDays($diasAtras)->toDateString(),
                'horaSesion' => '09:00',
                'duracionMinutos' => 90,
                'dirigidaA' => SesionAcademica::DIRIGIDA_TODOS,
                'modoAsistencia' => SesionAcademica::MODO_MANUAL,
            ], $tecnico);

            $sesiones->abrirSesion($sesion);

            $listaAsistencias = $sesion->asistencias()->inRandomOrder()->get();
            foreach ($listaAsistencias as $indice => $asistencia) {
                if ($indice >= 5) {
                    // El resto queda presente para no dejar la sesión con
                    // ausentismo irreal.
                    $asistencias->corregirMarca($asistencia, AsistenciaAcademica::ESTADO_PRESENTE);
                }
            }

            $sesiones->confirmarYCerrarSesion($sesion->fresh());

            $ausentes = AsistenciaAcademica::where('idSesion', $sesion->idSesion)
                ->where('estadoAsistencia', AsistenciaAcademica::ESTADO_AUSENTE)
                ->limit(3)
                ->get();

            $acciones = ['aprobar', 'rechazar', 'dejar_pendiente'];
            foreach ($ausentes as $indice => $asistenciaAusente) {
                $justificacion = $justificaciones->crear($asistenciaAusente, [
                    'motivo' => 'Cita médica no aplazable (dato de prueba).',
                ]);

                $accion = $acciones[$indice % count($acciones)];
                if ($accion === 'aprobar') {
                    $justificaciones->aprobar($justificacion, $sancionesUsuario);
                } elseif ($accion === 'rechazar') {
                    $justificaciones->rechazar($justificacion, 'No se adjuntó soporte médico (dato de prueba).', $sancionesUsuario);
                }
            }
        }
        $this->line('Sesiones recientes con justificaciones creadas: 2 (aprobada/rechazada/pendiente).');
    }
}
