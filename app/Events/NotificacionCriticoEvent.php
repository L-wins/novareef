<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Partido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionCriticoEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Partido $partido) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("colegio.{$this->partido->idColegio}.partidos");
    }

    public function broadcastAs(): string
    {
        return 'partido.critico';
    }

    public function broadcastWith(): array
    {
        return [
            'idPartido'       => $this->partido->idPartido,
            'equipoLocal'     => $this->partido->equipoLocal,
            'equipoVisitante' => $this->partido->equipoVisitante,
            'fechaPartido'    => $this->partido->fechaPartido?->toDateString(),
            'horaPartido'     => $this->partido->horaPartido,
            'mensaje'         => '⚠️ Partido crítico — se requiere acción inmediata',
        ];
    }
}
