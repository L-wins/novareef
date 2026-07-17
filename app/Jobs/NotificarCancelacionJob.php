<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PartidoCanceladoMail;
use App\Models\NotificacionEnviada;
use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarCancelacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public readonly Partido $partido) {}

    public function handle(): void
    {
        $partido = $this->partido->load([
            'designaciones.arbitro.usuario',
            'torneo',
            'sede',
        ]);

        foreach ($partido->designaciones as $designacion) {
            $email = $designacion->arbitro?->usuario?->emailUsuario;
            if (! $email) {
                continue;
            }

            // Incluye version: dedup solo dentro de la misma transición (protege
            // contra reintentos de cola) sin bloquear una notificación legítima
            // si el partido llega a cancelarse más de una vez en su historial.
            $referencia = "{$partido->idPartido}:{$partido->version}";
            if (! NotificacionEnviada::reclamar('cancelacion', $referencia, $email)) {
                continue;
            }

            try {
                Mail::to($email)->send(new PartidoCanceladoMail($partido, $designacion));
            } catch (\Throwable $e) {
                report($e);
                Log::error("NotificarCancelacionJob: fallo email. idPartido={$partido->idPartido}, idArbitro={$designacion->idArbitro}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
