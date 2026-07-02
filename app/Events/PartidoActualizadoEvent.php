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

class PartidoActualizadoEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Partido $partido) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("colegio.{$this->partido->idColegio}.partidos");
    }

    public function broadcastAs(): string
    {
        return 'partido.actualizado';
    }

    public function broadcastWith(): array
    {
        $partido = $this->partido->load([
            'torneo',
            'division',
            'sede',
            'formato',
            'designaciones.arbitro.usuario',
            'designaciones.rol',
        ]);

        return [
            'idPartido'       => $partido->idPartido,
            'equipoLocal'     => $partido->equipoLocal,
            'equipoVisitante' => $partido->equipoVisitante,
            'fechaPartido'    => $partido->fechaPartido?->toDateString(),
            'horaPartido'     => $partido->horaPartido,
            'estadoPartido'   => $partido->estadoPartido,
            'version'         => $partido->version,
            'torneo'          => $partido->torneo?->only(['idTorneo', 'nombreTorneo']),
            'division'        => $partido->division?->only(['idDivision', 'nombreDivision']),
            'sede'            => $partido->sede?->only(['idSede', 'nombreSede']),
            'designaciones'   => $partido->designaciones->map(fn ($d) => [
                'idDesignacion'    => $d->idDesignacion,
                'idRol'            => $d->idRol,
                'estadoDesignacion'=> $d->estadoDesignacion,
                'arbitro'          => $d->arbitro ? [
                    'idArbitro'    => $d->arbitro->idArbitro,
                    'nombre'       => $d->arbitro->usuario?->nombreUsuario,
                    'codigoCarnet' => $d->arbitro->codigoCarnet,
                ] : null,
                'rol' => $d->rol?->only(['idRol', 'nombre']),
            ])->values()->all(),
        ];
    }
}
