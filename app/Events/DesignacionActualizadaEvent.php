<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Designacion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DesignacionActualizadaEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Designacion $designacion) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("colegio.{$this->designacion->idColegio}.designaciones");
    }

    public function broadcastAs(): string
    {
        return 'designacion.actualizada';
    }

    public function broadcastWith(): array
    {
        $d = $this->designacion->load(['arbitro.usuario', 'rol']);

        return [
            'idDesignacion'    => $d->idDesignacion,
            'idPartido'        => $d->idPartido,
            'idColegio'        => $d->idColegio,
            'idRol'            => $d->idRol,
            'estadoDesignacion'=> $d->estadoDesignacion,
            'motivoRechazo'    => $d->motivoRechazo,
            'fechaConfirmacion'=> $d->fechaConfirmacion?->toIso8601String(),
            'arbitro'          => $d->arbitro ? [
                'idArbitro'    => $d->arbitro->idArbitro,
                'nombre'       => $d->arbitro->usuario?->nombreUsuario,
                'codigoCarnet' => $d->arbitro->codigoCarnet,
                'estadoArbitro'=> $d->arbitro->estadoArbitro,
            ] : null,
            'rol' => $d->rol?->only(['idRol', 'nombre']),
        ];
    }
}
