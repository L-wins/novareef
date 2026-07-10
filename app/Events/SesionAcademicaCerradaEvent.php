<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SesionAcademica;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SesionAcademicaCerradaEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly SesionAcademica $sesion) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("colegio.{$this->sesion->idColegio}.academico");
    }

    public function broadcastAs(): string
    {
        return 'sesion.cerrada';
    }

    public function broadcastWith(): array
    {
        return [
            'idSesion'     => $this->sesion->idSesion,
            'estadoSesion' => $this->sesion->estadoSesion,
        ];
    }
}
