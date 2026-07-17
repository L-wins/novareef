<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PartidoFinalizadoMail;
use App\Models\NotificacionEnviada;
use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarFinalizacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public readonly Partido $partido) {}

    public function handle(): void
    {
        $partido = $this->partido->load([
            'designaciones.arbitro.usuario',
            'designaciones.rol',
            'torneo',
            'sede',
            'division',
        ]);

        $referencia = "{$partido->idPartido}:{$partido->version}";

        foreach ($partido->designaciones as $designacion) {
            $email = $designacion->arbitro?->usuario?->emailUsuario;
            if (! $email) {
                continue;
            }

            if (! NotificacionEnviada::reclamar('finalizacion', $referencia, $email)) {
                continue;
            }

            try {
                Mail::to($email)->send(new PartidoFinalizadoMail($partido, $designacion));
            } catch (\Throwable $e) {
                report($e);
                Log::error("NotificarFinalizacionJob: fallo email. idPartido={$partido->idPartido}, idArbitro={$designacion->idArbitro}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
