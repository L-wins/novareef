<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DesignacionRechazadaMail;
use App\Models\Designacion;
use App\Models\NotificacionEnviada;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarRechazoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public readonly Designacion $designacion) {}

    public function handle(): void
    {
        $designacion = $this->designacion->load([
            'partido.torneo',
            'partido.division',
            'partido.sede',
            'arbitro.usuario',
            'designador',
            'rol',
        ]);

        $emailDesignador = $designacion->designador?->emailUsuario;

        if (! $emailDesignador) {
            Log::warning("NotificarRechazoJob: designador sin email. idDesignacion={$designacion->idDesignacion}");
            return;
        }

        if (NotificacionEnviada::reclamar('rechazo', (string) $designacion->idDesignacion, $emailDesignador)) {
            try {
                Mail::to($emailDesignador)->send(new DesignacionRechazadaMail($designacion));
            } catch (\Throwable $e) {
                report($e);
                Log::error("NotificarRechazoJob: fallo email. idDesignacion={$designacion->idDesignacion}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->enviarSmsDesignador($designacion);
    }

    private function enviarSmsDesignador(Designacion $designacion): void
    {
        if (empty(config('services.twilio.sid'))) {
            return;
        }

        $telefono = $designacion->designador?->telefonoUsuario;
        if (! $telefono) {
            return;
        }

        $partido = $designacion->partido;
        $arbitro = $designacion->arbitro?->usuario?->nombreUsuario ?? 'El árbitro';

        $mensaje = "NovaReef: {$arbitro} rechazó su designación.\n"
            . "{$partido->equipoLocal} vs {$partido->equipoVisitante}.\n"
            . "Gestiona en: " . route('designaciones.show', $partido->idPartido);

        try {
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create($telefono, [
                'from' => config('services.twilio.from'),
                'body' => $mensaje,
            ]);
        } catch (\Throwable $e) {
            Log::warning("NotificarRechazoJob: fallo SMS designador. idDesignacion={$designacion->idDesignacion}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
