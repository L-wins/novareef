<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\Partido;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula candidatos y advertencias para asignar un árbitro a un partido:
 * disponibilidad reportada, indisponibilidad extraordinaria, suspensión y
 * choques de horario con otros partidos del día. Separado de
 * DesignacionService (que se quedó con la escritura: crear/asignar/
 * confirmar/rechazar) porque ese archivo superaba las ~700 líneas
 * documentadas y este cálculo es un bloque cohesivo propio — ver auditoría
 * de plataforma, punto 3.1. DesignacionService sigue usando esta clase
 * internamente (vía inyección) para las advertencias que muestra al asignar.
 */
final class CandidatosDesignacionService
{
    private const MINUTOS_MARGEN_PARTIDO_CERCANO = 120;

    /**
     * Lista de árbitros candidatos para un partido, con sus indicadores de
     * disponibilidad y ordenados: disponibles sin advertencias → con
     * advertencias → sin reporte → suspendidos al final.
     *
     * @return Collection<int, array>
     */
    public function candidatosParaPartido(Partido $partido, int $idColegio): Collection
    {
        $fecha      = $partido->fechaPartido;
        $franjaNeed = $this->franjaDesdeHora($partido->horaPartido ?? '00:00');

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->whereNotIn('estadoArbitro', ['retirado'])
            ->with([
                'usuario',
                'categoria',
                'disponibilidades' => fn ($q) => $q->where('fechaDisponibilidad', $fecha),
                'indisponibilidadesExtraordinarias' => fn ($q) => $q->where('fechaAfectada', $fecha),
            ])
            ->get();

        $yaAsignados = $partido->designaciones()->pluck('idArbitro')->flip();

        // Choques de horario para todos los árbitros con una sola query
        $advertenciasPorArbitro = $this->advertenciasPorLista($arbitros, $partido);

        $resultado = $arbitros->map(function (Arbitro $a) use ($franjaNeed, $yaAsignados, $advertenciasPorArbitro): array {
            $disponibilidad = $a->disponibilidades->first();
            $extraordinaria = $a->indisponibilidadesExtraordinarias->first();

            $dispEstado  = 'sin_reporte';
            $franjaDisp  = null;
            $franjaLabel = null;

            if ($extraordinaria) {
                $dispEstado = 'extraordinaria';
            } elseif ($disponibilidad) {
                // Distinguir los tres casos reales: reportó y cubre la hora del
                // partido (disponible), reportó otra franja (otra_franja — antes
                // se mostraba como "sin disponibilidad", confundiendo al
                // designador), o se declaró explícitamente no disponible.
                $dispEstado = match (true) {
                    $disponibilidad->franjaHoraria === DisponibilidadArbitro::FRANJA_NO_DISPONIBLE => 'no_disponible',
                    $this->franjaCoincide($disponibilidad->franjaHoraria, $franjaNeed)             => 'disponible',
                    default                                                                        => 'otra_franja',
                };
                $franjaDisp  = $disponibilidad->franjaHoraria;
                $franjaLabel = $disponibilidad->franjaLegible();
            }

            $advertencias = $advertenciasPorArbitro[$a->idArbitro];

            return [
                'idArbitro'               => $a->idArbitro,
                'nombreUsuario'           => $a->usuario?->nombreUsuario,
                'codigoCarnet'            => $a->codigoCarnet,
                'numeroDocumento'         => $a->numeroDocumento,
                'nombreCategoria'         => $a->categoria?->nombreCategoria,
                'disponibilidad'          => $dispEstado,
                'franjaDisponible'        => $franjaDisp,
                'franjaLabel'             => $franjaLabel,
                'advertenciaTiempo'       => $advertencias['advertenciaTiempo'],
                'minutosAlPartidoCercano' => $advertencias['minutosAlPartidoCercano'],
                'horaPartidoCercano'      => $advertencias['horaPartidoCercano'],
                'partidosHoy'             => $advertencias['partidosHoy'],
                'yaAsignado'              => isset($yaAsignados[$a->idArbitro]),
                'esSuspendido'            => $a->estadoArbitro === 'suspendido',
                'estadoArbitro'           => $a->estadoArbitro,
            ];
        });

        // Ningún árbitro se excluye de la lista de candidatos por su
        // disponibilidad: el designador debe poder ver y elegir a cualquiera
        // (incluyendo "no disponible" e indisponibilidad extraordinaria) para
        // casos de emergencia o reportes incorrectos. Solo se ordenan al
        // final con su insignia de advertencia correspondiente — la exclusión
        // es una decisión del designador, no del sistema.
        // Orden: disponibles sin advertencias → con advertencias → otra
        //        franja → sin reporte → no disponible/extraordinaria
        //        → suspendidos → ya asignados al final
        return $resultado
            ->sortBy(fn ($r) => match (true) {
                $r['yaAsignado']                                                       => 99,
                $r['esSuspendido']                                                     => 5,
                in_array($r['disponibilidad'], ['no_disponible', 'extraordinaria'], true) => 4,
                $r['disponibilidad'] === 'sin_reporte'                                 => 3,
                $r['disponibilidad'] === 'otra_franja'                                 => 2,
                $r['advertenciaTiempo']                                                => 1,
                default                                                                => 0,
            })->values();
    }

    /**
     * Calcula las advertencias a mostrar al designador antes de confirmar
     * la asignación de un árbitro a un partido: disponibilidad, indisponibilidad
     * extraordinaria, suspensión y choques de horario con otros partidos del día.
     *
     * @return array{
     *     sinDisponibilidad: bool,
     *     noDisponible: bool,
     *     otraFranja: bool,
     *     franjaReportada: string|null,
     *     tieneExtraordinaria: bool,
     *     advertenciaTiempo: bool,
     *     minutosAlPartidoCercano: int|null,
     *     horaPartidoCercano: string|null,
     *     esSuspendido: bool,
     *     partidosHoy: int,
     * }
     */
    public function calcularAdvertencias(Arbitro $arbitro, Partido $partido): array
    {
        // Comparar con isSameDay: las relaciones pueden venir cargadas sin
        // filtro de fecha (asignar/reasignar) y ambas columnas son casts
        // Carbon — un where() suelto contra string nunca coincide.
        $disponibilidad = $arbitro->disponibilidades
            ->first(fn (DisponibilidadArbitro $d) => $d->fechaDisponibilidad->isSameDay($partido->fechaPartido));

        $extraordinaria = $arbitro->indisponibilidadesExtraordinarias
            ->first(fn ($i) => $i->fechaAfectada->isSameDay($partido->fechaPartido));

        $noDisponible = $disponibilidad?->franjaHoraria === DisponibilidadArbitro::FRANJA_NO_DISPONIBLE;
        $otraFranja   = $disponibilidad !== null
            && ! $noDisponible
            && ! $this->franjaCoincide(
                $disponibilidad->franjaHoraria,
                $this->franjaDesdeHora($partido->horaPartido ?? '00:00')
            );

        $otrasDesignacionesDelDia = $this->designacionesDelDia($partido, $arbitro->idArbitro);

        [$advertenciaTiempo, $minutosAlPartidoCercano, $horaPartidoCercano] = $this->detectarPartidoCercano(
            $partido,
            $otrasDesignacionesDelDia
        );

        return [
            'sinDisponibilidad'       => $disponibilidad === null,
            'noDisponible'            => $noDisponible,
            'otraFranja'              => $otraFranja,
            'franjaReportada'         => $disponibilidad?->franjaLegible(),
            'tieneExtraordinaria'     => $extraordinaria !== null,
            'advertenciaTiempo'       => $advertenciaTiempo,
            'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
            'horaPartidoCercano'      => $horaPartidoCercano,
            'esSuspendido'            => $arbitro->estadoArbitro === 'suspendido',
            'partidosHoy'             => $otrasDesignacionesDelDia->count(),
        ];
    }

    /**
     * Variante para listas de árbitros (ej. selector de disponibles): calcula
     * los choques de horario con una sola query en lugar de una por árbitro.
     *
     * @param  Collection<int, Arbitro>  $arbitros
     * @return array<int, array{advertenciaTiempo: bool, minutosAlPartidoCercano: int|null,
     *         horaPartidoCercano: string|null, partidosHoy: int}>
     *         indexado por idArbitro
     */
    public function advertenciasPorLista(Collection $arbitros, Partido $partido): array
    {
        $designacionesDia = Designacion::whereIn('idArbitro', $arbitros->pluck('idArbitro'))
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get()
            ->groupBy('idArbitro');

        return $arbitros->mapWithKeys(function (Arbitro $arbitro) use ($partido, $designacionesDia): array {
            $otrasDelDia = $designacionesDia->get($arbitro->idArbitro, collect());

            [$advertenciaTiempo, $minutosAlPartidoCercano, $horaPartidoCercano] = $this->detectarPartidoCercano(
                $partido,
                $otrasDelDia
            );

            return [$arbitro->idArbitro => [
                'advertenciaTiempo'       => $advertenciaTiempo,
                'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
                'horaPartidoCercano'      => $horaPartidoCercano,
                'partidosHoy'             => $otrasDelDia->count(),
            ]];
        })->all();
    }

    // ── Helpers privados ──────────────────

    private function franjaDesdeHora(string $hora): string
    {
        $h = (int) substr($hora, 0, 2);

        return match (true) {
            $h >= 6  && $h < 12 => 'am',
            $h >= 12 && $h < 18 => 'pm',
            default             => 'noche',
        };
    }

    private function franjaCoincide(string $franjaArbitro, string $franjaPartido): bool
    {
        $mapa = [
            'am'         => ['am'],
            'pm'         => ['pm'],
            'noche'      => ['noche'],
            'am_pm'      => ['am', 'pm'],
            'am_noche'   => ['am', 'noche'],
            'pm_noche'   => ['pm', 'noche'],
            'todo_el_dia'=> ['am', 'pm', 'noche'],
        ];

        return in_array($franjaPartido, $mapa[$franjaArbitro] ?? [], true);
    }

    /**
     * Designaciones activas (pendiente o confirmada) del árbitro en la MISMA
     * fecha del partido, excluyendo este mismo partido. Una designación
     * rechazada no representa un compromiso real y no debe contar ni como
     * choque de horario ni como sobrecarga del día.
     */
    private function designacionesDelDia(Partido $partido, int $idArbitro): Collection
    {
        return Designacion::where('idArbitro', $idArbitro)
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get();
    }

    /**
     * @param  Collection<int, Designacion>  $otrasDesignacionesDelDia
     * @return array{0: bool, 1: int|null, 2: string|null}
     */
    private function detectarPartidoCercano(Partido $partido, Collection $otrasDesignacionesDelDia): array
    {
        $horaPartido = Carbon::createFromFormat('H:i', substr($partido->horaPartido ?? '00:00', 0, 5));

        foreach ($otrasDesignacionesDelDia as $otra) {
            $otraHoraRaw = substr($otra->partido->horaPartido ?? '00:00', 0, 5);
            $otraHora    = Carbon::createFromFormat('H:i', $otraHoraRaw);
            $diff        = abs($horaPartido->diffInMinutes($otraHora));

            if ($diff < self::MINUTOS_MARGEN_PARTIDO_CERCANO) {
                return [true, $diff, $otraHora->format('g:i A')];
            }
        }

        return [false, null, null];
    }
}
