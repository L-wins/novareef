<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ConfiguracionColegio;
use App\Models\Designacion;
use App\Models\HistorialDesignacion;
use App\Models\Partido;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerificarConfirmacionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Marca como CRÍTICOS los partidos programados con designaciones pendientes
     * cuyo plazo de confirmación (configurable por colegio) ya venció.
     * El plazo corre desde el momento más tardío entre la asignación del árbitro
     * y la publicación del partido (antes de publicar el árbitro no ve nada).
     */
    public function handle(): void
    {
        $pendientes = Designacion::where('estadoDesignacion', Designacion::ESTADO_PENDIENTE)
            ->whereHas('partido', fn ($q) => $q->where('estadoPartido', Partido::ESTADO_PROGRAMADO))
            ->with('partido')
            ->get();

        $partidosMarcados      = 0;
        $designacionesVencidas = 0;

        // Agrupar por partido: varias designaciones vencidas → una sola transición
        foreach ($pendientes->groupBy('idPartido') as $idPartido => $grupo) {
            $partido = $grupo->first()->partido;

            // Pudo haber cambiado de estado por una transición previa en este mismo ciclo
            if ($partido->estadoPartido !== Partido::ESTADO_PROGRAMADO) {
                continue;
            }

            $horasLimite = ConfiguracionColegio::getHorasLimiteConfirmacion((int) $partido->idColegio);
            $corte       = now()->subHours($horasLimite);

            $publicadoEn = HistorialDesignacion::where('idPartido', $idPartido)
                ->where('tipoAccion', HistorialDesignacion::TIPO_ESTADO_PARTIDO_CAMBIADO)
                ->where('estadoNuevo', Partido::ESTADO_PROGRAMADO)
                ->latest('created_at')
                ->value('created_at');

            $vencidas = $grupo->filter(function (Designacion $d) use ($corte, $publicadoEn): bool {
                $inicioPlazo = collect([$d->created_at, $publicadoEn])->filter()->max();

                return $inicioPlazo !== null && $inicioPlazo->lte($corte);
            });

            if ($vencidas->isEmpty()) {
                continue;
            }

            $designacionesVencidas += $vencidas->count();

            try {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_CRITICO,
                    null,
                    'Árbitro no confirmó en el tiempo límite'
                );

                NotificarCriticoJob::dispatch($partido, 'Árbitro no confirmó en el tiempo límite');

                $partidosMarcados++;
            } catch (\Throwable $e) {
                Log::error("VerificarConfirmacionesJob: error al marcar partido {$idPartido} como crítico", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('VerificarConfirmacionesJob ejecutado', [
            'designacionesVencidas' => $designacionesVencidas,
            'partidosMarcados'      => $partidosMarcados,
        ]);
    }
}
