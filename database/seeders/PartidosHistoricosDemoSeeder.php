<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Console\Commands\Support\GeneradorDatosColombianos;
use App\Models\Arbitro;
use App\Models\CalificacionArbitro;
use App\Models\Colegio;
use App\Models\Designacion;
use App\Models\DivisionTorneo;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\SedeTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Genera partidos HISTÓRICOS (fechas pasadas) para ASOCAFA pasando por el
 * flujo real de negocio (DesignacionService + PartidoStateMachine, mismo
 * patrón que valida DesignacionFlujoTest/CalificacionControllerTest), para
 * poder probar el módulo de Estadísticas con datos que parezcan reales:
 * partidos finalizados con roles variados (Central/Asistente/Cuarto),
 * designaciones rechazadas (confiabilidad), calificaciones de veedor, y
 * "escuadras" de árbitros que se repiten entre partidos (coincidencias).
 *
 * Mail::fake() + Queue::fake() envuelven todo el run() — mismo aislamiento
 * que ya usa app/Console/Commands/SembrarDatosCargaCommand.php para no
 * disparar correos reales (MAIL_MAILER=resend) ni encolar jobs reales
 * (QUEUE_CONNECTION=database) al publicar/finalizar decenas de partidos.
 * Además el torneo se crea con modalidadPago='campo' (no 'nomina'), así que
 * aunque se olvidara el fake, GenerarPagosJob nunca se dispararía (ver
 * PartidoStateMachine::efectosFinalizacion() + FinanzasService, doble seguro
 * ya usado en ese mismo comando).
 *
 *   php artisan db:seed --class=PartidosHistoricosDemoSeeder
 */
class PartidosHistoricosDemoSeeder extends Seeder
{
    private const NOMBRE_COLEGIO  = 'ASOCAFA';
    private const TOTAL_PARTIDOS  = 70;
    private const TAMANO_ESCUADRA = 5;
    private const NUM_ESCUADRAS   = 10;

    private const MOTIVOS_RECHAZO = [
        'Compromiso familiar de último momento',
        'Cruce con otro partido ya confirmado',
        'Problema de transporte a la sede',
        'Indisposición médica',
    ];

    private const COMENTARIOS_CALIFICACION = [
        'Buen manejo del partido, criterio consistente en jugadas de riesgo.',
        'Puntualidad y presentación correctas. Podría mejorar la comunicación con el resto del equipo arbitral.',
        'Excelente control del juego, sin mayores incidentes.',
        'Desempeño sólido, aunque con algo de duda en el manejo de tiempo adicional.',
    ];

    public function run(DesignacionService $designacionService): void
    {
        Mail::fake();
        Queue::fake();

        $colegio = Colegio::where('nombreColegio', self::NOMBRE_COLEGIO)->first();
        if (! $colegio) {
            $this->command->warn('Colegio "' . self::NOMBRE_COLEGIO . '" no encontrado — omitido.');
            return;
        }

        $designador = User::where('idColegio', $colegio->idColegio)->where('rolUsuario', 'ejecutivo')->first();
        if (! $designador) {
            $this->command->warn('El colegio no tiene un usuario ejecutivo — omitido.');
            return;
        }

        $veedor = $this->obtenerOCrearVeedor($colegio);

        $arbitros = Arbitro::where('idColegio', $colegio->idColegio)
            ->where('estadoArbitro', 'activo')
            ->with('usuario')
            ->get();

        if ($arbitros->count() < self::TAMANO_ESCUADRA * 2) {
            $this->command->warn('Muy pocos árbitros activos para generar escuadras — omitido.');
            return;
        }

        $torneos = $this->crearTorneos($colegio, $designador);

        $formatoDupla       = FormatoDesignacion::where('nombre', 'Dupla')->firstOrFail();
        $formatoCuartoTerna = FormatoDesignacion::where('nombre', 'Cuarto-Terna')->firstOrFail();
        $rolCentral         = RolPartido::where('nombre', 'Central')->value('idRol');
        $rolAsistente       = RolPartido::where('nombre', 'Asistente')->value('idRol');
        $rolCuarto          = RolPartido::where('nombre', 'Cuarto')->value('idRol');

        // "Escuadras" fijas que se repiten entre partidos — sin esto, con
        // selección puramente aleatoria sobre ~95 árbitros casi nunca
        // coincidirían dos veces y la sección de Coincidencias quedaría vacía.
        $escuadras = $arbitros->shuffle()->chunk(self::TAMANO_ESCUADRA)->take(self::NUM_ESCUADRAS)->values();

        $datos = new GeneradorDatosColombianos();

        $creados = 0;
        $finalizados = 0;
        $rechazados = 0;
        $calificados = 0;

        for ($i = 0; $i < self::TOTAL_PARTIDOS; $i++) {
            $torneo   = $torneos[$i % count($torneos)];
            $cuartoTerna = $i % 3 === 0;
            $formato  = $cuartoTerna ? $formatoCuartoTerna : $formatoDupla;
            $necesita = $cuartoTerna ? 4 : 2;

            $escuadra = $escuadras[$i % $escuadras->count()];
            $roster   = $escuadra->count() >= $necesita
                ? $escuadra->shuffle()->take($necesita)->values()
                : $arbitros->random($necesita)->values();

            [$local, $visitante] = $datos->dosEquiposDistintos();
            $fecha = now()->subDays(random_int(10, 300));

            $partido = $designacionService->crearPartido($colegio->idColegio, [
                'idTorneo'        => $torneo['torneo']->idTorneo,
                'idDivision'      => $torneo['division']->idDivision,
                'idSede'          => $torneo['sede']->idSede,
                'idFormato'       => $formato->idFormato,
                'equipoLocal'     => $local,
                'equipoVisitante' => $visitante,
                'fechaPartido'    => $fecha->toDateString(),
                'horaPartido'     => sprintf('%02d:%02d', [8, 10, 14, 15, 16, 18][array_rand([8, 10, 14, 15, 16, 18])], 0),
                'observaciones'   => null,
            ], $designador->idUsuario);

            $asignaciones = [];
            $asignaciones[] = $this->asignar($designacionService, $partido, $roster[0], $rolCentral, $colegio->idColegio, $designador->idUsuario);
            $asignaciones[] = $this->asignar($designacionService, $partido, $roster[1], $rolAsistente, $colegio->idColegio, $designador->idUsuario);

            if ($cuartoTerna) {
                $asignaciones[] = $this->asignar($designacionService, $partido, $roster[2], $rolAsistente, $colegio->idColegio, $designador->idUsuario);
                $asignaciones[] = $this->asignar($designacionService, $partido, $roster[3], $rolCuarto, $colegio->idColegio, $designador->idUsuario);
            }

            $designacionService->publicarPartido($partido->fresh('formato'), $designador);
            $creados++;

            $suerte = $i % 20;

            if ($suerte === 0) {
                // Una designación se rechaza — alimenta el reporte de confiabilidad.
                $rechazo = $asignaciones[array_rand($asignaciones)];
                $designacionService->rechazarDesignacion(
                    $rechazo['designacion']->fresh(),
                    $rechazo['arbitro'],
                    self::MOTIVOS_RECHAZO[array_rand(self::MOTIVOS_RECHAZO)],
                    $designador,
                );
                $rechazados++;
                continue; // el partido queda crítico, sin finalizar — realista
            }

            foreach ($asignaciones as $a) {
                $designacionService->confirmarDesignacion($a['designacion']->fresh(), $a['arbitro'], $designador);
            }

            if ($suerte < 15) {
                PartidoStateMachine::transicionarCon(
                    $partido->fresh(),
                    Partido::ESTADO_FINALIZADO,
                    $designador,
                    'Partido finalizado (dato histórico de prueba)',
                );
                $finalizados++;

                if ($suerte % 2 === 0) {
                    foreach ($asignaciones as $a) {
                        CalificacionArbitro::create([
                            'idDesignacion' => $a['designacion']->idDesignacion,
                            'idVeedor'      => $veedor->idUsuario,
                            'idColegio'     => $colegio->idColegio,
                            'nota'          => round(random_int(30, 50) / 10, 1),
                            'comentario'    => self::COMENTARIOS_CALIFICACION[array_rand(self::COMENTARIOS_CALIFICACION)],
                        ]);
                        $calificados++;
                    }
                }
            }
            // suerte >= 15: queda 'confirmado' sin finalizar — backlog realista.
        }

        $this->command->info(
            "✓ {$creados} partidos históricos creados en {$colegio->nombreColegio} — "
            . "{$finalizados} finalizados, {$rechazados} con una designación rechazada, "
            . "{$calificados} designaciones calificadas por el veedor."
        );
    }

    /** @return array{designacion: Designacion, arbitro: Arbitro} */
    private function asignar(DesignacionService $servicio, Partido $partido, Arbitro $arbitro, int $idRol, int $idColegio, int $idUsuarioAccion): array
    {
        $resultado = $servicio->asignarArbitro($partido, $arbitro->idArbitro, $idRol, $idColegio, $idUsuarioAccion);

        return ['designacion' => $resultado['designacion'], 'arbitro' => $arbitro];
    }

    private function obtenerOCrearVeedor(Colegio $colegio): User
    {
        $veedor = User::where('idColegio', $colegio->idColegio)->where('rolUsuario', 'veedor')->first();
        if ($veedor) {
            return $veedor;
        }

        $veedor = User::create([
            'idColegio'            => $colegio->idColegio,
            'nombreUsuario'        => 'Veedor Demo',
            'emailUsuario'         => 'veedor.demo@asocafa.test',
            'passwordUsuario'      => Hash::make('password'),
            'rolUsuario'           => 'veedor',
            'estadoUsuario'        => 'activo',
            'must_change_password' => false,
        ]);

        if (\Spatie\Permission\Models\Role::where('name', 'veedor')->where('guard_name', 'web')->exists()) {
            $veedor->assignRole('veedor');
        }

        return $veedor;
    }

    /** @return list<array{torneo: Torneo, division: DivisionTorneo, sede: SedeTorneo}> */
    private function crearTorneos(Colegio $colegio, User $creador): array
    {
        $resultado = [];

        foreach ([(int) now()->year - 1, (int) now()->year] as $temporada) {
            $torneo = Torneo::firstOrCreate(
                ['idColegio' => $colegio->idColegio, 'nombreTorneo' => "Liga ASOCAFA {$temporada}"],
                [
                    'tipoTorneo'        => 'local',
                    'modalidadPago'     => 'campo', // evita GenerarPagosJob (nómina) — ver docblock de la clase
                    'estadoTorneo'      => 'activo',
                    'organizadorNombre' => 'ASOCAFA',
                    'temporada'         => $temporada,
                    'fechaInicio'       => "{$temporada}-01-15",
                    'fechaFin'          => "{$temporada}-11-30",
                    'idUsuarioCreador'  => $creador->idUsuario,
                ],
            );

            $division = DivisionTorneo::firstOrCreate(
                ['idTorneo' => $torneo->idTorneo, 'nombreDivision' => 'Primera División'],
            );

            $sede = SedeTorneo::firstOrCreate(
                ['idTorneo' => $torneo->idTorneo, 'nombreSede' => 'Complejo Deportivo Central'],
                ['ciudad' => 'Bogotá'],
            );

            $resultado[] = ['torneo' => $torneo, 'division' => $division, 'sede' => $sede];
        }

        return $resultado;
    }
}
