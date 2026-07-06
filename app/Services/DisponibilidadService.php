<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\IndisponibilidadExtraordinariaMail;
use App\Models\Arbitro;
use App\Models\ConfiguracionColegio;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\IndisponibilidadExtraordinaria;
use App\Models\User;
use App\Support\SemanaNavegacion;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class DisponibilidadService
{
    /**
     * Verdadero si el árbitro ya reportó su disponibilidad para la semana en curso.
     * Única fuente de verdad — antes se repetía esta misma query en index() y store().
     */
    public function yaReportoEstaSemana(Arbitro $arbitro): bool
    {
        $diaInicio = ConfiguracionColegio::getDiaDisponibilidad($arbitro->idColegio);

        return DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '>=', SemanaNavegacion::desde(null, $diaInicio)->lunes->toDateString())
            ->exists();
    }

    /**
     * Guarda la disponibilidad semanal del árbitro: crea/actualiza los días con
     * franja seleccionada y elimina los que se dejaron en blanco. Solo se puede
     * reportar una vez por semana.
     *
     * @param  array<int, array{fecha:string, franja:?string}>  $disponibilidades
     * @throws \RuntimeException  Si el árbitro ya reportó disponibilidad esta semana.
     */
    public function guardarSemana(Arbitro $arbitro, array $disponibilidades): void
    {
        if ($this->yaReportoEstaSemana($arbitro)) {
            throw new \RuntimeException('Ya guardaste tu disponibilidad para esta semana. Podrás modificarla la próxima semana.');
        }

        DB::transaction(function () use ($arbitro, $disponibilidades): void {
            foreach ($disponibilidades as $item) {
                $fecha  = $item['fecha'];
                $franja = $item['franja'] ?? null;

                if ($franja !== null && $franja !== '') {
                    DisponibilidadArbitro::updateOrCreate(
                        ['idArbitro' => $arbitro->idArbitro, 'fechaDisponibilidad' => $fecha],
                        ['franjaHoraria' => $franja, 'motivo' => null],
                    );
                } else {
                    DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
                        ->where('fechaDisponibilidad', $fecha)
                        ->delete();
                }
            }
        });
    }

    /**
     * Elimina la disponibilidad reportada de un día puntual. Si el árbitro tenía
     * designaciones confirmadas en esa fecha, registra una indisponibilidad
     * extraordinaria y notifica al designador.
     *
     * @return bool  true si afectó designaciones confirmadas (se notificó al designador).
     */
    public function marcarNoDisponible(Arbitro $arbitro, string $fecha, string $motivo, int $idColegio, int $idUsuarioAccion): bool
    {
        DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', $fecha)
            ->delete();

        return $this->registrarExtraordinariaSiAfecta(
            $arbitro,
            $fecha,
            DisponibilidadArbitro::FRANJA_TODO_DIA,
            $motivo,
            $idColegio,
            $idUsuarioAccion,
        );
    }

    /**
     * Registra una indisponibilidad extraordinaria (fuera del ciclo semanal normal).
     * Si afecta designaciones confirmadas, notifica al designador.
     *
     * @return bool  true si afectó designaciones confirmadas (se notificó al designador).
     */
    public function registrarIndisponibilidadExtraordinaria(
        Arbitro $arbitro,
        string  $fecha,
        string  $franja,
        string  $motivo,
        int     $idColegio,
        int     $idUsuarioAccion,
    ): bool {
        return $this->registrarExtraordinariaSiAfecta($arbitro, $fecha, $franja, $motivo, $idColegio, $idUsuarioAccion);
    }

    /**
     * Disponibilidad e indisponibilidades de un árbitro en las próximas 2 semanas,
     * para que el designador consulte antes de asignarlo. Formato listo para JSON.
     *
     * @return array{disponibilidades: array, indisponibilidades: array}
     */
    public function resumenParaDesignador(int $idArbitro): array
    {
        $hoy    = now()->startOfDay();
        $limite = $hoy->copy()->addWeeks(2)->endOfWeek(\Carbon\Carbon::SUNDAY);
        $rango  = [$hoy->toDateString(), $limite->toDateString()];

        $disponibilidades = DisponibilidadArbitro::where('idArbitro', $idArbitro)
            ->whereBetween('fechaDisponibilidad', $rango)
            ->orderBy('fechaDisponibilidad')
            ->get()
            ->map(fn (DisponibilidadArbitro $d) => [
                'fecha'       => $d->fechaDisponibilidad->format('Y-m-d'),
                'franja'      => $d->franjaHoraria,
                'franjaLabel' => $d->franjaLegible(),
            ])
            ->all();

        $indisponibilidades = IndisponibilidadExtraordinaria::where('idArbitro', $idArbitro)
            ->whereBetween('fechaAfectada', $rango)
            ->orderBy('fechaAfectada')
            ->get()
            ->map(fn (IndisponibilidadExtraordinaria $i) => [
                'fecha'  => $i->fechaAfectada->format('Y-m-d'),
                'franja' => $i->franjaAfectada,
                'motivo' => $i->motivo,
            ])
            ->all();

        return compact('disponibilidades', 'indisponibilidades');
    }

    // ── Helpers privados ──────────────────

    private function registrarExtraordinariaSiAfecta(
        Arbitro $arbitro,
        string  $fecha,
        string  $franja,
        string  $motivo,
        int     $idColegio,
        int     $idUsuarioAccion,
    ): bool {
        IndisponibilidadExtraordinaria::create([
            'idArbitro'         => $arbitro->idArbitro,
            'idColegio'         => $idColegio,
            'fechaAfectada'     => $fecha,
            'franjaAfectada'    => $franja,
            'motivo'            => $motivo,
            'idUsuarioRegistro' => $idUsuarioAccion,
        ]);

        $designacionesAfectadas = $this->designacionesConfirmadasEnFecha($arbitro->idArbitro, $fecha);

        if ($designacionesAfectadas->isEmpty()) {
            return false;
        }

        $this->notificarIndisponibilidad($arbitro, $fecha, $franja, $motivo, $designacionesAfectadas);

        return true;
    }

    /**
     * Retorna las designaciones confirmadas del árbitro en una fecha dada.
     */
    private function designacionesConfirmadasEnFecha(int $idArbitro, string $fecha): EloquentCollection
    {
        return Designacion::where('idArbitro', $idArbitro)
            ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->whereHas('partido', fn ($q) => $q->whereDate('fechaPartido', $fecha))
            ->with('partido.torneo')
            ->get();
    }

    /**
     * Notifica al designador (o ejecutivo) del colegio cuando un árbitro
     * registra una indisponibilidad extraordinaria con partidos confirmados.
     *
     * @param  EloquentCollection<int, Designacion>  $partidosAfectados
     */
    private function notificarIndisponibilidad(
        Arbitro           $arbitro,
        string             $fecha,
        string             $franja,
        string             $motivo,
        EloquentCollection $partidosAfectados,
    ): void {
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
            Log::error('[DisponibilidadService] Error enviando mail de indisponibilidad', [
                'idArbitro' => $arbitro->idArbitro,
                'idColegio' => $arbitro->idColegio,
                'fecha'     => $fecha,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
