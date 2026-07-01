<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\NotificacionCriticoEvent;
use App\Mail\PartidoCriticoMail;
use App\Models\Partido;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarCriticoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries     = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Partido $partido,
        public readonly ?string $motivo = null,
    ) {}

    /**
     * Notifica que un partido pasó a CRÍTICO: broadcast en tiempo real al panel
     * del colegio y email a designadores y ejecutivos.
     */
    public function handle(): void
    {
        $partido = $this->partido->load(['torneo', 'sede']);

        broadcast(new NotificacionCriticoEvent($partido));

        $destinatarios = User::where('idColegio', $partido->idColegio)
            ->whereIn('rolUsuario', ['designador', 'ejecutivo'])
            ->pluck('emailUsuario')
            ->filter();

        foreach ($destinatarios as $email) {
            try {
                Mail::to($email)->send(new PartidoCriticoMail($partido, $this->motivo));
            } catch (\Throwable $e) {
                Log::error("NotificarCriticoJob: fallo email a {$email}. idPartido={$partido->idPartido}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
