<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\Sancion;
use App\Models\TipoSancion;
use App\Models\User;
use App\Services\SancionService;
use App\Services\TipoSancionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Siembra un catálogo de tipos de sanción y sanciones de prueba realistas
 * para el colegio ASOCAFA — mismo criterio que
 * SembrarFinanzasAcademicoAsocafaCommand: un solo colegio ya existente,
 * usando SancionService/TipoSancionService reales (nunca insertando estados
 * a mano) para cubrir variedad de severidades, con/sin multa, con/sin
 * suspensión, y todos los estados del ciclo de vida (activa, cumplida,
 * anulada, apelada con sus dos resoluciones).
 *
 * No es idempotente a propósito — pensado para correr una sola vez sobre un
 * ASOCAFA sin sanciones todavía (el tipo "Clase Teórica" que trae de otro
 * seeder se deja intacto, este comando agrega los suyos aparte).
 */
class SembrarSancionesAsocafaCommand extends Command
{
    protected $signature = 'novareef:sembrar-sanciones-asocafa';

    protected $description = 'Siembra el catálogo de tipos de sanción y sanciones disciplinarias de prueba en el colegio ASOCAFA';

    public function handle(SancionService $sanciones, TipoSancionService $tiposSancion): int
    {
        mt_srand(20260724);

        Mail::fake();
        Queue::fake();

        $asocafa = Colegio::where('codigoColegio', 'ASOCAFA')->orWhere('nombreColegio', 'ASOCAFA')->first();

        if (! $asocafa) {
            $this->error('No se encontró el colegio ASOCAFA.');

            return self::FAILURE;
        }

        $idColegio = $asocafa->idColegio;

        $comiteDisciplinario = User::where('idColegio', $idColegio)->where('rolUsuario', 'sanciones')->first()
            ?? User::where('idColegio', $idColegio)->where('rolUsuario', 'ejecutivo')->firstOrFail();

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->inRandomOrder()
            ->limit(30)
            ->get();

        if ($arbitros->count() < 15) {
            $this->error('Muy pocos árbitros activos en ASOCAFA para sembrar sanciones.');

            return self::FAILURE;
        }

        $this->info('=== Sembrando catálogo y sanciones en ASOCAFA ===');

        $tipos = $this->sembrarCatalogo($tiposSancion, $idColegio);
        $this->sembrarSanciones($sanciones, $idColegio, $tipos, $arbitros, $comiteDisciplinario);

        $this->info('=== Listo ===');

        return self::SUCCESS;
    }

    // ── Catálogo de tipos (reglamento disciplinario típico de un colegio de árbitros) ──

    /**
     * @return array<string, TipoSancion>  Indexado por una clave corta para referenciar en sembrarSanciones().
     */
    private function sembrarCatalogo(TipoSancionService $tiposSancion, int $idColegio): array
    {
        $this->info("\n--- Catálogo de tipos de sanción ---");

        $definiciones = [
            'impuntualidad' => [
                'etiqueta' => 'Impuntualidad en la presentación al partido',
                'articuloReglamento' => 'Art. 14',
                'severidad' => TipoSancion::SEVERIDAD_LEVE,
                'diasSuspensionSugeridos' => 0,
                'descripcion' => 'El árbitro se presenta después de la hora de citación fijada por el designador, sin causa justificada.',
            ],
            'uniforme' => [
                'etiqueta' => 'Incumplimiento del reglamento de uniforme',
                'articuloReglamento' => 'Art. 15',
                'severidad' => TipoSancion::SEVERIDAD_LEVE,
                'diasSuspensionSugeridos' => 0,
                'descripcion' => 'Presentación al partido sin el uniforme oficial completo o en mal estado.',
            ],
            'inasistencia_academica' => [
                'etiqueta' => 'Inasistencia injustificada a capacitación',
                'articuloReglamento' => 'Art. 18',
                'severidad' => TipoSancion::SEVERIDAD_LEVE,
                'diasSuspensionSugeridos' => 0,
                'descripcion' => 'No asistir a una sesión académica obligatoria sin justificación aprobada.',
            ],
            'acta_extemporanea' => [
                'etiqueta' => 'Entrega extemporánea del acta de partido',
                'articuloReglamento' => 'Art. 21',
                'severidad' => TipoSancion::SEVERIDAD_LEVE,
                'diasSuspensionSugeridos' => 1,
                'descripcion' => 'Radicación del acta fuera del plazo establecido tras la finalización del partido.',
            ],
            'conducta_indebida' => [
                'etiqueta' => 'Conducta indebida con delegados o dirigentes',
                'articuloReglamento' => 'Art. 27',
                'severidad' => TipoSancion::SEVERIDAD_MODERADA,
                'diasSuspensionSugeridos' => 15,
                'descripcion' => 'Trato irrespetuoso o altercado con delegados, dirigentes o personal organizador del torneo.',
            ],
            'error_tecnico_reiterado' => [
                'etiqueta' => 'Errores técnicos reiterados en la dirección del partido',
                'articuloReglamento' => 'Art. 29',
                'severidad' => TipoSancion::SEVERIDAD_MODERADA,
                'diasSuspensionSugeridos' => 30,
                'descripcion' => 'Aplicación incorrecta y reiterada del reglamento de juego, verificada por el Comité Técnico.',
            ],
            'abandono_puesto' => [
                'etiqueta' => 'Abandono injustificado del puesto de trabajo',
                'articuloReglamento' => 'Art. 31',
                'severidad' => TipoSancion::SEVERIDAD_MODERADA,
                'diasSuspensionSugeridos' => 45,
                'descripcion' => 'No presentarse a un partido asignado sin previo aviso ni causa de fuerza mayor.',
            ],
            'falsedad_acta' => [
                'etiqueta' => 'Falsedad en el acta de partido',
                'articuloReglamento' => 'Art. 34',
                'severidad' => TipoSancion::SEVERIDAD_GRAVE,
                'diasSuspensionSugeridos' => 90,
                'descripcion' => 'Consignar hechos falsos o alterar información relevante en el acta oficial del partido.',
            ],
            'agresion' => [
                'etiqueta' => 'Agresión física o verbal grave',
                'articuloReglamento' => 'Art. 36',
                'severidad' => TipoSancion::SEVERIDAD_GRAVE,
                'diasSuspensionSugeridos' => 180,
                'descripcion' => 'Agresión física o verbal grave contra jugadores, dirigentes, compañeros árbitros o veedores.',
            ],
            'conducta_antideportiva_grave' => [
                'etiqueta' => 'Conducta antideportiva o antiética grave',
                'articuloReglamento' => 'Art. 38',
                'severidad' => TipoSancion::SEVERIDAD_GRAVE,
                'diasSuspensionSugeridos' => 365,
                'descripcion' => 'Actos de corrupción, manipulación de resultados o afectación grave a la integridad del arbitraje.',
            ],
        ];

        $tipos = [];
        foreach ($definiciones as $clave => $datos) {
            $tipos[$clave] = $tiposSancion->crear($idColegio, $datos);
        }

        $this->line('Tipos de sanción creados: ' . count($tipos));

        return $tipos;
    }

    // ── Sanciones ────────────────────────────

    /**
     * @param  array<string, TipoSancion>  $tipos
     * @param  \Illuminate\Support\Collection<int, Arbitro>  $arbitros
     */
    private function sembrarSanciones(SancionService $sanciones, int $idColegio, array $tipos, $arbitros, User $comite): void
    {
        $this->info("\n--- Sanciones ---");

        $arbitrosDisponibles = $arbitros->values();
        $totalCreadas = 0;

        // 1) Leves antiguas, ya cumplidas — sin multa, sin suspensión (amonestación).
        $motivosLeves = [
            'impuntualidad' => 'Se presentó 25 minutos tarde a la citación del partido de la fecha 8, sin previo aviso al designador.',
            'uniforme' => 'Dirigió el partido con camiseta distinta a la asignada por el colegio para la categoría.',
            'inasistencia_academica' => 'No asistió a la capacitación de actualización de reglas de juego sin presentar justificación.',
            'acta_extemporanea' => 'Radicó el acta del partido tres días después del plazo establecido.',
        ];
        foreach (range(1, 10) as $i) {
            $clave = array_rand($motivosLeves);
            $arbitro = $arbitrosDisponibles[($i - 1) % $arbitrosDisponibles->count()];
            $fechaHecho = now()->subDays(mt_rand(60, 200))->toDateString();

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipos[$clave]->idTipoSancion,
                'motivoSancion' => $motivosLeves[$clave],
                'fechaHecho' => $fechaHecho,
                'tieneMultaEconomica' => false,
            ], $comite);

            // Se registran ya vencido el plazo de apelación y se dan por
            // cumplidas (amonestación verbal/escrita ya notificada).
            $sanciones->cumplir($sancion, $comite, 'Amonestación notificada y sostenida — sin apelación dentro del plazo.');
            $totalCreadas++;
        }
        $this->line('Sanciones leves cumplidas creadas: 10.');

        // 2) Leves con multa económica pequeña, algunas pagadas otras pendientes
        //    (el estado del movimiento financiero lo resuelve FinanzasService
        //    internamente con estadoMovimiento=pendiente por defecto).
        foreach (range(1, 6) as $i) {
            $arbitro = $arbitrosDisponibles[($i + 3) % $arbitrosDisponibles->count()];
            $fechaHecho = now()->subDays(mt_rand(30, 150))->toDateString();

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipos['acta_extemporanea']->idTipoSancion,
                'motivoSancion' => 'Reincidencia en entrega extemporánea del acta pese a llamado de atención previo.',
                'fechaHecho' => $fechaHecho,
                'tieneMultaEconomica' => true,
                'montoMulta' => 20000,
            ], $comite);

            if ($i % 2 === 0) {
                $sanciones->cumplir($sancion, $comite);
            }
            $totalCreadas++;
        }
        $this->line('Sanciones leves con multa creadas: 6.');

        // 3) Moderadas activas, con suspensión temporal vigente — algunas con
        //    multa. fechaInicioSancion/fechaFinSancion ya transcurriendo.
        $motivosModerados = [
            'conducta_indebida' => 'Discusión acalorada con el delegado del equipo visitante al finalizar el partido, con señalamientos por parte de veedor.',
            'error_tecnico_reiterado' => 'Tercera anotación del Comité Técnico en la temporada por errores reiterados en la aplicación del fuera de lugar.',
            'abandono_puesto' => 'No se presentó al partido asignado en la fecha 12 sin dar aviso previo al designador ni presentar excusa posterior.',
        ];
        $clavesModeradas = array_keys($motivosModerados);
        foreach (range(1, 8) as $i) {
            $clave = $clavesModeradas[($i - 1) % count($clavesModeradas)];
            $arbitro = $arbitrosDisponibles[($i + 9) % $arbitrosDisponibles->count()];
            $tipo = $tipos[$clave];
            $fechaHecho = now()->subDays(mt_rand(10, 45))->toDateString();
            $diasSuspension = $tipo->diasSuspensionSugeridos ?? 15;

            $conMulta = $i % 3 === 0;

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipo->idTipoSancion,
                'idPartido' => null,
                'motivoSancion' => $motivosModerados[$clave],
                'fechaHecho' => $fechaHecho,
                'fechaInicioSancion' => now()->subDays(mt_rand(1, 8))->toDateString(),
                'fechaFinSancion' => now()->addDays($diasSuspension - mt_rand(1, 8))->toDateString(),
                'tieneMultaEconomica' => $conMulta,
                'montoMulta' => $conMulta ? 50000 : null,
            ], $comite);

            $totalCreadas++;
        }
        $this->line('Sanciones moderadas activas (suspensión vigente) creadas: 8.');

        // 4) Graves — suspensiones largas ya cumplidas (temporada anterior),
        //    con multa alta.
        $motivosGraves = [
            'falsedad_acta' => 'Se comprobó alteración del resultado consignado en el acta respecto a lo transmitido por el veedor de turno.',
            'agresion' => 'Agresión verbal grave contra un dirigente tras la finalización del partido, con testigos y reporte del veedor.',
        ];
        $clavesGraves = array_keys($motivosGraves);
        foreach (range(1, 4) as $i) {
            $clave = $clavesGraves[($i - 1) % count($clavesGraves)];
            $arbitro = $arbitrosDisponibles[($i + 17) % $arbitrosDisponibles->count()];
            $tipo = $tipos[$clave];
            $fechaHecho = now()->subDays(mt_rand(220, 320))->toDateString();

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipo->idTipoSancion,
                'motivoSancion' => $motivosGraves[$clave],
                'fechaHecho' => $fechaHecho,
                'fechaInicioSancion' => now()->subDays(mt_rand(200, 300))->toDateString(),
                'fechaFinSancion' => now()->subDays(mt_rand(30, 90))->toDateString(),
                'tieneMultaEconomica' => true,
                'montoMulta' => 150000,
            ], $comite);

            $sanciones->cumplir($sancion, $comite, 'Suspensión cumplida en su totalidad — árbitro reincorporado.');
            $totalCreadas++;
        }
        $this->line('Sanciones graves ya cumplidas creadas: 4.');

        // 5) Anuladas — error de registro, sin multa previa con abonos.
        foreach (range(1, 3) as $i) {
            $arbitro = $arbitrosDisponibles[($i + 21) % $arbitrosDisponibles->count()];

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipos['uniforme']->idTipoSancion,
                'motivoSancion' => 'Reporte inicial del veedor por incumplimiento de uniforme.',
                'fechaHecho' => now()->subDays(mt_rand(15, 60))->toDateString(),
                'tieneMultaEconomica' => false,
            ], $comite);

            $sanciones->anular($sancion, $comite, 'Se verificó con el veedor que el árbitro sí portaba el uniforme autorizado por el colegio — error de registro.');
            $totalCreadas++;
        }
        $this->line('Sanciones anuladas (error de registro) creadas: 3.');

        // 6) Apeladas, aún sin resolver — creadas hace pocos días para que el
        //    plazo de apelación (Sancion::DIAS_LIMITE_APELACION) siga vigente.
        foreach (range(1, 2) as $i) {
            $arbitro = $arbitrosDisponibles[($i + 24) % $arbitrosDisponibles->count()];

            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitro->idArbitro,
                'idTipoSancion' => $tipos['conducta_indebida']->idTipoSancion,
                'motivoSancion' => 'Presunta discusión con delegado local — el árbitro sostiene que el reporte del veedor no refleja lo ocurrido.',
                'fechaHecho' => now()->subDays(2)->toDateString(),
                'fechaInicioSancion' => now()->toDateString(),
                'fechaFinSancion' => now()->addDays(13)->toDateString(),
                'tieneMultaEconomica' => false,
            ], $comite);

            $sanciones->apelar($sancion, $comite, 'El árbitro radicó apelación adjuntando su versión de los hechos y video del partido.');
            $totalCreadas++;
        }
        $this->line('Sanciones apeladas (pendientes de resolver) creadas: 2.');

        // 7) Apelación resuelta — confirmada (sostiene, pasa a cumplida).
        $arbitroConfirmada = $arbitrosDisponibles[26 % $arbitrosDisponibles->count()];
        $sancionConfirmada = $sanciones->crearSancion($idColegio, [
            'idArbitro' => $arbitroConfirmada->idArbitro,
            'idTipoSancion' => $tipos['error_tecnico_reiterado']->idTipoSancion,
            'motivoSancion' => 'Comité Técnico verificó tres errores graves de aplicación reglamentaria en el mismo partido.',
            'fechaHecho' => now()->subDays(4)->toDateString(),
            'fechaInicioSancion' => now()->subDays(1)->toDateString(),
            'fechaFinSancion' => now()->addDays(29)->toDateString(),
            'tieneMultaEconomica' => false,
        ], $comite);
        $sanciones->apelar($sancionConfirmada, $comite, 'El árbitro solicitó revisión del video VAR del partido.');
        $sanciones->resolverApelacion($sancionConfirmada, 'confirmada', $comite, 'El Comité Técnico revisó el video y confirmó los tres errores señalados — se sostiene la sanción.');
        $totalCreadas++;
        $this->line('Sanción con apelación confirmada (sostenida) creada: 1.');

        // 8) Apelación resuelta — revocada (anula la sanción y la multa asociada).
        $arbitroRevocada = $arbitrosDisponibles[27 % $arbitrosDisponibles->count()];
        $sancionRevocada = $sanciones->crearSancion($idColegio, [
            'idArbitro' => $arbitroRevocada->idArbitro,
            'idTipoSancion' => $tipos['acta_extemporanea']->idTipoSancion,
            'motivoSancion' => 'Acta radicada fuera de plazo según reporte inicial de secretaría.',
            'fechaHecho' => now()->subDays(3)->toDateString(),
            'tieneMultaEconomica' => true,
            'montoMulta' => 20000,
        ], $comite);
        $sanciones->apelar($sancionRevocada, $comite, 'El árbitro presentó comprobante de radicación dentro del plazo, con sello de recibido de secretaría.');
        $sanciones->resolverApelacion($sancionRevocada, 'revocada', $comite, 'Se verificó el comprobante de radicación a tiempo — la sanción y la multa quedan sin efecto.');
        $totalCreadas++;
        $this->line('Sanción con apelación revocada (anulada) creada: 1.');

        // 9) Reincidente — mismo árbitro con 3 sanciones no anuladas en los
        //    últimos 6 meses, para poder ver la alerta de SancionService::esReincidente().
        $arbitroReincidente = $arbitrosDisponibles[28 % $arbitrosDisponibles->count()];
        foreach ([50, 25, 5] as $diasAtras) {
            $sancion = $sanciones->crearSancion($idColegio, [
                'idArbitro' => $arbitroReincidente->idArbitro,
                'idTipoSancion' => $tipos['impuntualidad']->idTipoSancion,
                'motivoSancion' => 'Llegada tarde a la citación del partido — segunda ocasión en el semestre para este árbitro.',
                'fechaHecho' => now()->subDays($diasAtras)->toDateString(),
                'tieneMultaEconomica' => false,
            ], $comite);

            if ($diasAtras > 10) {
                $sanciones->cumplir($sancion, $comite);
            }
            $totalCreadas++;
        }
        $this->line("Sanciones de árbitro reincidente creadas: 3 (árbitro #{$arbitroReincidente->idArbitro}).");

        $this->line("\nTotal de sanciones creadas: {$totalCreadas}.");
    }
}
