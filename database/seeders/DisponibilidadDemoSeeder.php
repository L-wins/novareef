<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Arbitro;
use App\Models\Colegio;
use App\Models\ConfiguracionColegio;
use App\Models\DisponibilidadArbitro;
use App\Models\IndisponibilidadExtraordinaria;
use App\Support\SemanaNavegacion;
use Illuminate\Database\Seeder;

/**
 * Puebla disponibilidad_arbitros (y algunas indisponibilidades_extraordinarias)
 * con datos variados para un colegio real, para poder ver/probar la vista
 * "Disponibilidad semanal" (disponibilidad.general) con navegación entre
 * semanas sin que todo salga vacío. Solo demo/QA — no forma parte de
 * DatabaseSeeder, se corre aparte:
 *   php artisan db:seed --class=DisponibilidadDemoSeeder
 */
class DisponibilidadDemoSeeder extends Seeder
{
    private const NOMBRE_COLEGIO = 'ASOCAFA';

    /** Franjas normales (sin "no_disponible", esa se usa aparte para el patrón C). */
    private const FRANJAS = [
        DisponibilidadArbitro::FRANJA_AM,
        DisponibilidadArbitro::FRANJA_PM,
        DisponibilidadArbitro::FRANJA_NOCHE,
        DisponibilidadArbitro::FRANJA_AM_PM,
        DisponibilidadArbitro::FRANJA_AM_NOCHE,
        DisponibilidadArbitro::FRANJA_PM_NOCHE,
        DisponibilidadArbitro::FRANJA_TODO_DIA,
    ];

    public function run(): void
    {
        $colegio = Colegio::where('nombreColegio', self::NOMBRE_COLEGIO)->first();

        if (! $colegio) {
            $this->command->warn('Colegio "' . self::NOMBRE_COLEGIO . '" no encontrado — omitido.');
            return;
        }

        $arbitros = Arbitro::where('idColegio', $colegio->idColegio)
            ->where('estadoArbitro', 'activo')
            ->orderBy('idArbitro')
            ->get();

        if ($arbitros->isEmpty()) {
            $this->command->warn('El colegio no tiene árbitros activos — omitido.');
            return;
        }

        $diaCiclo = ConfiguracionColegio::getDiaDisponibilidad($colegio->idColegio);

        // Semana anterior, actual y siguiente — para que navegar con
        // prev/next muestre datos distintos en las tres.
        $actual = SemanaNavegacion::desde(null, $diaCiclo, recortarAHoy: false);
        $semanas = [
            SemanaNavegacion::desde($actual->semanaPrev, $diaCiclo, recortarAHoy: false),
            $actual,
            SemanaNavegacion::desde($actual->semanaNext, $diaCiclo, recortarAHoy: false),
        ];

        foreach ($semanas as $semana) {
            $this->poblarSemana($arbitros, $semana, $colegio->idColegio);
        }

        $this->command->info(
            '✓ Disponibilidad demo creada para ' . $arbitros->count() . " árbitros de {$colegio->nombreColegio} "
            . '(3 semanas: ' . $semanas[0]->lunes->toDateString() . ' → ' . $semanas[2]->domingo->toDateString() . ').'
        );
    }

    private function poblarSemana($arbitros, SemanaNavegacion $semana, int $idColegio): void
    {
        foreach ($arbitros as $i => $arbitro) {
            $patron = $i % 5;

            foreach ($semana->dias as $dia) {
                $fecha = $dia->toDateString();

                match ($patron) {
                    // A: siempre disponible todo el día — el "árbitro modelo"
                    0 => $this->guardar($arbitro->idArbitro, $fecha, DisponibilidadArbitro::FRANJA_TODO_DIA),

                    // B: solo entre semana (lun-vie), franja variable; fin de semana sin reportar
                    1 => $dia->isWeekday()
                        ? $this->guardar($arbitro->idArbitro, $fecha, self::FRANJAS[$dia->dayOfWeek % count(self::FRANJAS)])
                        : null,

                    // C: mezcla de disponible y "no disponible" explícito
                    2 => $this->guardar(
                        $arbitro->idArbitro,
                        $fecha,
                        $dia->dayOfWeek % 3 === 0
                            ? DisponibilidadArbitro::FRANJA_NO_DISPONIBLE
                            : self::FRANJAS[$dia->dayOfWeek % count(self::FRANJAS)],
                        $dia->dayOfWeek % 3 === 0 ? 'Compromiso personal' : null,
                    ),

                    // D: reporta parcial (solo 2-3 días) — el resto queda "sin reporte"
                    3 => in_array($dia->dayOfWeekIso, [2, 4, 6], true)
                        ? $this->guardar($arbitro->idArbitro, $fecha, DisponibilidadArbitro::FRANJA_AM_PM)
                        : null,

                    // E: no reporta nada esta semana (simula un árbitro despistado)
                    default => null,
                };
            }

            // Un puñado de indisponibilidades extraordinarias sueltas, para
            // ver el badge "Extraord." en la tabla — solo en la semana actual
            // y solo para un subconjunto pequeño (1 de cada 12 árbitros).
            if ($semana->esActual() && $i % 12 === 0) {
                $diaExtra = $semana->dias->get(2) ?? $semana->dias->first();

                if ($diaExtra) {
                    IndisponibilidadExtraordinaria::updateOrCreate(
                        [
                            'idArbitro'     => $arbitro->idArbitro,
                            'fechaAfectada' => $diaExtra->toDateString(),
                        ],
                        [
                            'idColegio'         => $idColegio,
                            'franjaAfectada'    => DisponibilidadArbitro::FRANJA_TODO_DIA,
                            'motivo'            => 'Imprevisto médico de último momento',
                            'idUsuarioRegistro' => $arbitro->idUsuario,
                        ]
                    );
                }
            }
        }
    }

    private function guardar(int $idArbitro, string $fecha, string $franja, ?string $motivo = null): void
    {
        DisponibilidadArbitro::updateOrCreate(
            ['idArbitro' => $idArbitro, 'fechaDisponibilidad' => $fecha],
            ['franjaHoraria' => $franja, 'motivo' => $motivo],
        );
    }
}
