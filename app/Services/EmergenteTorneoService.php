<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\EmergenteTorneo;
use App\Models\Torneo;

final class EmergenteTorneoService
{
    /**
     * Asigna un árbitro emergente (suplente) a una sede/fecha del torneo.
     *
     * @throws \RuntimeException  Si el árbitro no está activo, pertenece a otro
     *                            colegio, o ya está asignado como emergente esa fecha.
     */
    public function asignar(
        Torneo  $torneo,
        Arbitro $arbitro,
        int     $idColegio,
        int     $idSede,
        string  $fechaEmergente,
        ?string $notas,
        int     $idUsuarioAsignador,
    ): EmergenteTorneo {
        if ($arbitro->estadoArbitro !== 'activo') {
            throw new \RuntimeException('El árbitro seleccionado no está activo.');
        }

        if ((int) $arbitro->idColegio !== $idColegio) {
            throw new \RuntimeException('El árbitro pertenece a otro colegio.');
        }

        $yaAsignado = EmergenteTorneo::where('idTorneo', $torneo->idTorneo)
            ->where('idArbitro', $arbitro->idArbitro)
            ->where('fechaEmergente', $fechaEmergente)
            ->exists();

        if ($yaAsignado) {
            throw new \RuntimeException('Este árbitro ya está asignado como emergente en esa fecha para este torneo.');
        }

        return EmergenteTorneo::create([
            'idTorneo'           => $torneo->idTorneo,
            'idArbitro'          => $arbitro->idArbitro,
            'idSede'             => $idSede,
            'idColegio'          => $idColegio,
            'fechaEmergente'     => $fechaEmergente,
            'notas'              => $notas,
            'idUsuarioAsignador' => $idUsuarioAsignador,
        ]);
    }
}
