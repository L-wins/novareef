<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\ImportacionPartidos;
use App\Models\MovimientoFinanciero;
use App\Models\Partido;
use App\Services\DesignacionService;
use Illuminate\Support\Facades\DB;

/**
 * Deshace una importación ya confirmada: borra únicamente los partidos que
 * ESA importación creó (idPartidoCreado en cada fila, no una heurística por
 * fecha/equipo). Es deliberadamente conservador — igual que
 * DesignacionService::eliminarPartido(), solo actúa sobre partidos en
 * borrador sin movimientos financieros; si alguno ya se publicó o generó
 * pagos, la reversión completa se bloquea para no destruir trabajo real
 * hecho después de la importación (designaciones confirmadas, nómina, etc.).
 */
final class RevertirImportacionService
{
    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

    /**
     * @throws \RuntimeException  Si la importación no está confirmada, o si
     *                             algún partido creado ya no está en borrador
     *                             o tiene movimientos financieros asociados.
     */
    public function revertir(ImportacionPartidos $importacion, int $idColegio, int $idUsuarioAccion): void
    {
        if (! $importacion->puedeRevertirse()) {
            throw new \RuntimeException('Solo se puede revertir una importación confirmada.');
        }

        $partidos = Partido::where('idImportacion', $importacion->idImportacion)
            ->where('idColegio', $idColegio)
            ->get();

        $bloqueantes = $partidos->filter(fn (Partido $p) => $p->estadoPartido !== Partido::ESTADO_BORRADOR
            || MovimientoFinanciero::where('idPartido', $p->idPartido)->exists());

        if ($bloqueantes->isNotEmpty()) {
            $nombres = $bloqueantes->map(fn (Partido $p) => "{$p->equipoLocal} vs {$p->equipoVisitante}")->implode(', ');
            throw new \RuntimeException(
                "No se puede revertir: los siguientes partidos ya no están en borrador o tienen movimientos financieros asociados: {$nombres}. "
                . 'Elimínalos o cancélalos manualmente uno por uno si corresponde.',
            );
        }

        DB::transaction(function () use ($partidos, $importacion, $idColegio, $idUsuarioAccion) {
            foreach ($partidos as $partido) {
                $this->designaciones->eliminarPartido($partido, $idColegio);
            }

            $importacion->update([
                'estado'             => ImportacionPartidos::ESTADO_REVERTIDA,
                'revertidaEn'        => now(),
                'idUsuarioReversion' => $idUsuarioAccion,
            ]);
        });
    }
}
