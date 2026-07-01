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

class FinalizarPartidosAutomaticoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $corte = now()->subMinutes(150);

        $partidos = Partido::where('estadoPartido', Partido::ESTADO_EN_CURSO)
            ->where('horaInicio', '<=', $corte)
            ->get();

        foreach ($partidos as $partido) {
            try {
                // La state machine despacha NotificarFinalizacionJob en sus efectos
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_FINALIZADO,
                    null,
                    'Finalizado automáticamente por el sistema (150 min)'
                );

                Log::info("Partido {$partido->idPartido} finalizado automáticamente.");
            } catch (\Throwable $e) {
                Log::error("Error al finalizar partido {$partido->idPartido} automáticamente: {$e->getMessage()}");
            }
        }
    }
}
