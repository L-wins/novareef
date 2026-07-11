<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AsistenciaAcademica;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cubre las tres formas en que una asistencia cambia en tiempo real: marca
 * web del propio árbitro, registro por scanner, y corrección manual del
 * instructor — se distinguen por el campo `registradoPor` del payload.
 */
class AsistenciaActualizadaEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AsistenciaAcademica $asistencia) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("colegio.{$this->asistencia->idColegio}.academico");
    }

    public function broadcastAs(): string
    {
        return 'asistencia.actualizada';
    }

    public function broadcastWith(): array
    {
        return $this->asistencia->toRealtimePayload();
    }
}
