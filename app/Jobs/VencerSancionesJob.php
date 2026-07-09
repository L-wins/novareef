<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Sancion;
use App\Services\SancionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cierra automáticamente las sanciones cuya fechaFinSancion ya pasó,
 * marcándolas como cumplidas. Las sanciones con fechaFinSancion nula
 * (indefinida) quedan activas hasta que el Comité las resuelva manualmente.
 */
class VencerSancionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(SancionService $sanciones): void
    {
        $vencidas = Sancion::whereIn('estadoSancion', [Sancion::ESTADO_ACTIVA, Sancion::ESTADO_APELADA])
            ->whereNotNull('fechaFinSancion')
            ->where('fechaFinSancion', '<', now()->toDateString())
            ->get();

        $marcadas = 0;

        foreach ($vencidas as $sancion) {
            try {
                $sanciones->cumplir($sancion, null, 'Vencimiento automático — fechaFinSancion alcanzada');
                $marcadas++;
            } catch (\Throwable $e) {
                Log::error("VencerSancionesJob: error al cumplir sanción {$sancion->idSancion}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('VencerSancionesJob ejecutado', ['sancionesMarcadas' => $marcadas]);
    }
}
