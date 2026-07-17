<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\Partido;
use App\Services\DesignacionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crea en lote los partidos ya revisados/corregidos en el preview del
 * importador. Reutiliza DesignacionService::crearPartido() fila por fila
 * (no Partido::create() directo) para no perder slots ni historial —
 * transacción única todo-o-nada: el preview ya filtró errores antes de
 * llegar aquí, así que un fallo en este punto es la excepción, no el
 * camino feliz.
 */
final class ImportacionPartidosService
{
    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array{creados: int, partidos: Collection<int, Partido>}
     *
     * @throws \RuntimeException  Si alguna fila incluida sigue teniendo error bloqueante.
     */
    public function importarLote(int $idColegio, int $idTorneo, array $filas, int $idUsuarioAccion): array
    {
        $filasAImportar = array_values(array_filter($filas, fn (array $fila) => $fila['incluir']));

        foreach ($filasAImportar as $fila) {
            if ($fila['errores'] !== []) {
                throw new \RuntimeException(
                    "El partido {$fila['equipoLocal']} vs {$fila['equipoVisitante']} tiene errores sin resolver: "
                    . implode(' — ', $fila['errores']),
                );
            }
        }

        return DB::transaction(function () use ($filasAImportar, $idColegio, $idTorneo, $idUsuarioAccion) {
            $creados = collect();

            foreach ($filasAImportar as $fila) {
                $observaciones = trim((string) ($fila['grupoTexto'] ?? '')) ?: null;

                $partido = $this->designaciones->crearPartido($idColegio, [
                    'idTorneo'        => $idTorneo,
                    'idDivision'      => $fila['idDivisionMatch'],
                    'idSede'          => $fila['idSedeMatch'],
                    'idFormato'       => $fila['idFormato'],
                    'equipoLocal'     => $fila['equipoLocal'],
                    'equipoVisitante' => $fila['equipoVisitante'],
                    'fechaPartido'    => $fila['fechaPartido'],
                    'horaPartido'     => $fila['horaPartido'],
                    'observaciones'   => $observaciones,
                ], $idUsuarioAccion);

                $creados->push($partido);
            }

            return ['creados' => $creados->count(), 'partidos' => $creados];
        });
    }
}
