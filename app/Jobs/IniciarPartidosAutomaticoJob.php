<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Partido;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IniciarPartidosAutomaticoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Pasa a en_curso los partidos confirmados cuya hora de inicio ya llegó.
     * La state machine asigna horaInicio, con lo que la finalización
     * automática (150 min) queda encadenada.
     */
    public function handle(): void
    {
        $ahora = now();

        $partidos = Partido::where('estadoPartido', Partido::ESTADO_CONFIRMADO)
            ->where(function ($q) use ($ahora) {
                $q->whereDate('fechaPartido', '<', $ahora->toDateString())
                    ->orWhere(function ($q2) use ($ahora) {
                        $q2->whereDate('fechaPartido', $ahora->toDateString())
                            ->where('horaPartido', '<=', $ahora->format('H:i:s'));
                    });
            })
            ->get();

        foreach ($partidos as $partido) {
            try {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_EN_CURSO,
                    null,
                    'Iniciado automáticamente por el sistema (hora del partido)'
                );

                Log::info("Partido {$partido->idPartido} iniciado automáticamente.");
            } catch (\Throwable $e) {
                Log::error("Error al iniciar partido {$partido->idPartido} automáticamente: {$e->getMessage()}");
            }
        }
    }
}
