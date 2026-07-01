<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PartidoAplazadoMail;
use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarAplazamientoJob implements ShouldQueue
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

            try {
                Mail::to($email)->send(new PartidoAplazadoMail($partido, $designacion));
            } catch (\Throwable $e) {
                Log::error("NotificarAplazamientoJob: fallo email. idPartido={$partido->idPartido}, idArbitro={$designacion->idArbitro}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
