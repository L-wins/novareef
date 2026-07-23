<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DesignacionNotificacionMail;
use App\Models\Designacion;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarDesignacionJob implements ShouldQueue
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
            'rol',
        ]);

        // Sin este guard, un reintento de cola tras una caída parcial del
        // worker (Mail::send() ya llegó a destino, pero el job "falla" antes
        // de llegar al update de abajo) reenvía el mismo correo/SMS.
        if ($designacion->notificacionEnviada) {
            return;
        }

        $email = $designacion->arbitro?->usuario?->emailUsuario;

        if (! $email) {
            Log::warning("NotificarDesignacionJob: árbitro sin email. idDesignacion={$designacion->idDesignacion}");
            return;
        }

        try {
            Mail::to($email)->send(new DesignacionNotificacionMail($designacion));
        } catch (\Throwable $e) {
            report($e);
            Log::error("NotificarDesignacionJob: fallo email. idDesignacion={$designacion->idDesignacion}", [
                'error' => $e->getMessage(),
            ]);
        }

        $this->enviarSms($designacion);
        $this->enviarWhatsApp($designacion);

        $designacion->update([
            'notificacionEnviada' => true,
            'fechaNotificacion'   => now(),
        ]);
    }

    private function enviarSms(Designacion $designacion): void
    {
        if (empty(config('services.twilio.sid'))) {
            return;
        }

        $telefono = $designacion->arbitro?->usuario?->telefonoUsuario;
        if (! $telefono) {
            return;
        }

        $partido = $designacion->partido;
        $fecha   = $partido->fechaPartido?->locale('es')->isoFormat('D [de] MMMM');
        $hora    = $partido->horaPartido;
        $url     = route('mis-partidos.index');

        $mensaje = "NovaReef: Tienes una nueva designación.\n"
            . "{$partido->equipoLocal} vs {$partido->equipoVisitante}, {$fecha} {$hora}.\n"
            . "Confirma en: {$url}";

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
            Log::warning("NotificarDesignacionJob: fallo SMS. idDesignacion={$designacion->idDesignacion}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envío best-effort — nunca bloquea la notificación por correo/SMS
     * (try/catch independiente, mismo criterio que enviarSms()). Sin
     * teléfono o sin WHATSAPP_PLANTILLA_DESIGNACION configurada, no hace
     * nada — el correo sigue siendo el canal garantizado.
     */
    private function enviarWhatsApp(Designacion $designacion): void
    {
        $plantilla = config('services.whatsapp.plantilla_designacion');

        if (! $plantilla) {
            return;
        }

        $telefono = $designacion->arbitro?->usuario?->telefonoUsuario;
        if (! $telefono) {
            return;
        }

        $partido = $designacion->partido;
        $fecha   = $partido->fechaPartido?->locale('es')->isoFormat('D [de] MMMM') ?? '';
        $hora    = (string) $partido->horaPartido;

        try {
            // Los nombres de estas claves deben coincidir exactamente con
            // las variables {{equipos}}, {{fecha}}, {{hora}}, {{rol}},
            // {{url}} configuradas en la plantilla aprobada en Meta.
            $this->whatsapp()->enviarPlantilla($telefono, $plantilla, [
                'equipos' => "{$partido->equipoLocal} vs {$partido->equipoVisitante}",
                'fecha'   => $fecha,
                'hora'    => $hora,
                'rol'     => $designacion->rol?->nombre ?? '',
                'url'     => route('mis-partidos.index'),
            ]);
        } catch (\Throwable $e) {
            Log::warning("NotificarDesignacionJob: fallo WhatsApp. idDesignacion={$designacion->idDesignacion}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function whatsapp(): WhatsAppService
    {
        return app(WhatsAppService::class);
    }
}
