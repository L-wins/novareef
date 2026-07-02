<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Partido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarPublicacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries     = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public readonly Partido $partido) {}

    /**
     * Notifica a cada árbitro designado que el partido fue publicado.
     * Reutiliza NotificarDesignacionJob (email + SMS) por designación.
     */
    public function handle(): void
    {
        $partido = $this->partido->load([
            'designaciones.arbitro.usuario',
            'torneo',
            'sede',
        ]);

        foreach ($partido->designaciones as $designacion) {
            NotificarDesignacionJob::dispatch($designacion);
        }

        Log::info("Partido {$partido->idPartido} publicado — {$partido->designaciones->count()} árbitros notificados");
    }
}
