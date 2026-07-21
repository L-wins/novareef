<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Suscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pasa a 'vencida' cualquier suscripción activa/trial cuya fechaVencimiento
 * ya pasó. Es el único mecanismo que efectivamente corta el acceso de una
 * cancelación programada (SuscripcionService::cancelar()) — pero corre igual
 * para cualquier suscripción que simplemente nadie renovó, con o sin
 * cancelación explícita de por medio.
 */
class VencerSuscripcionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $vencidas = Suscripcion::whereIn('estado', Suscripcion::ESTADOS_VIGENTES)
            ->where('fechaVencimiento', '<', now()->toDateString())
            ->get();

        $marcadas = 0;

        foreach ($vencidas as $suscripcion) {
            try {
                $nota = $suscripcion->fechaCancelacion !== null
                    ? 'Cancelación efectiva — fechaVencimiento alcanzada.'
                    : 'Vencimiento automático — fechaVencimiento alcanzada sin renovación.';

                $suscripcion->update([
                    'estado' => 'vencida',
                    'notas'  => trim(($suscripcion->notas ? $suscripcion->notas . ' — ' : '') . $nota),
                ]);

                $marcadas++;
            } catch (\Throwable $e) {
                Log::error("VencerSuscripcionesJob: error al vencer suscripción {$suscripcion->idSuscripcion}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('VencerSuscripcionesJob ejecutado', ['suscripcionesVencidas' => $marcadas]);
    }
}
