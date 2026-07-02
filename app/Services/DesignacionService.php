<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\IndisponibilidadExtraordinariaMail;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\Partido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class DesignacionService
{
    private const MINUTOS_MARGEN_PARTIDO_CERCANO = 60;

    /**
     * Calcula las advertencias a mostrar al designador antes de confirmar
     * la asignación de un árbitro a un partido: disponibilidad, indisponibilidad
     * extraordinaria, suspensión y choques de horario con otros partidos del día.
     *
     * @return array{
     *     sinDisponibilidad: bool,
     *     tieneExtraordinaria: bool,
     *     advertenciaTiempo: bool,
     *     minutosAlPartidoCercano: int|null,
     *     esSuspendido: bool,
     * }
     */
    public function calcularAdvertencias(Arbitro $arbitro, Partido $partido): array
    {
        $disponibilidad = $arbitro->disponibilidades->first();
        $extraordinaria = $arbitro->indisponibilidadesExtraordinarias
            ->where('fechaAfectada', $partido->fechaPartido->toDateString())
            ->first();

        [$advertenciaTiempo, $minutosAlPartidoCercano] = $this->detectarPartidoCercano(
            $partido,
            $this->designacionesDelDia($partido, $arbitro->idArbitro)
        );

        return [
            'sinDisponibilidad'       => $disponibilidad === null,
            'tieneExtraordinaria'     => $extraordinaria !== null,
            'advertenciaTiempo'       => $advertenciaTiempo,
            'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
            'esSuspendido'            => $arbitro->estadoArbitro === 'suspendido',
        ];
    }

    /**
     * Variante para listas de árbitros (ej. selector de disponibles): calcula
     * los choques de horario con una sola query en lugar de una por árbitro.
     *
     * @param  Collection<int, Arbitro>  $arbitros
     * @return array<int, array{advertenciaTiempo: bool, minutosAlPartidoCercano: int|null}>
     *         indexado por idArbitro
     */
    public function advertenciasPorLista(Collection $arbitros, Partido $partido): array
    {
        $designacionesDia = Designacion::whereIn('idArbitro', $arbitros->pluck('idArbitro'))
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get()
            ->groupBy('idArbitro');

        return $arbitros->mapWithKeys(function (Arbitro $arbitro) use ($partido, $designacionesDia): array {
            [$advertenciaTiempo, $minutosAlPartidoCercano] = $this->detectarPartidoCercano(
                $partido,
                $designacionesDia->get($arbitro->idArbitro, collect())
            );

            return [$arbitro->idArbitro => [
                'advertenciaTiempo'       => $advertenciaTiempo,
                'minutosAlPartidoCercano' => $minutosAlPartidoCercano,
            ]];
        })->all();
    }

    /**
     * Notifica al designador (o ejecutivo) del colegio cuando un árbitro
     * registra una indisponibilidad extraordinaria con partidos confirmados.
     *
     * @param  EloquentCollection<int, Designacion>  $partidosAfectados
     */
    public function notificarIndisponibilidad(
        Arbitro           $arbitro,
        string             $fecha,
        string             $franja,
        string             $motivo,
        EloquentCollection $partidosAfectados,
    ): void {
        if ($partidosAfectados->isEmpty()) {
            return;
        }

        $designador = User::where('idColegio', $arbitro->idColegio)
            ->whereIn('rolUsuario', ['ejecutivo', 'designador'])
            ->first();

        if ($designador === null) {
            return;
        }

        try {
            Mail::to($designador->emailUsuario)->send(
                new IndisponibilidadExtraordinariaMail($arbitro, $fecha, $franja, $motivo, $partidosAfectados)
            );
        } catch (\Throwable $e) {
            Log::error('[DesignacionService] Error enviando mail de indisponibilidad', [
                'idArbitro'  => $arbitro->idArbitro,
                'idColegio'  => $arbitro->idColegio,
                'fecha'      => $fecha,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function designacionesDelDia(Partido $partido, int $idArbitro): Collection
    {
        return Designacion::where('idArbitro', $idArbitro)
            ->whereHas('partido', fn ($q) => $q->where('fechaPartido', $partido->fechaPartido)
                ->where('idPartido', '!=', $partido->idPartido))
            ->with('partido:idPartido,horaPartido')
            ->get();
    }

    /**
     * @param  Collection<int, Designacion>  $otrasDesignacionesDelDia
     * @return array{0: bool, 1: int|null}
     */
    private function detectarPartidoCercano(Partido $partido, Collection $otrasDesignacionesDelDia): array
    {
        $horaPartido = Carbon::createFromFormat('H:i', substr($partido->horaPartido ?? '00:00', 0, 5));

        foreach ($otrasDesignacionesDelDia as $otra) {
            $otraHora = Carbon::createFromFormat('H:i', substr($otra->partido->horaPartido ?? '00:00', 0, 5));
            $diff     = abs($horaPartido->diffInMinutes($otraHora));

            if ($diff < self::MINUTOS_MARGEN_PARTIDO_CERCANO) {
                return [true, $diff];
            }
        }

        return [false, null];
    }
}
