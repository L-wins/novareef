<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\Partido;

/**
 * Detecta si una fila del importador coincide con un partido que ya existe
 * en el torneo — reenviar el mismo .docx por error, o un archivo corregido
 * con solo 2 filas cambiadas de 40, son los casos reales que motivan esto.
 * La hora se excluye a propósito: una reprogramación de horario menor no
 * debería tratarse como "otro partido" para efectos de duplicado.
 */
final class DeteccionDuplicadosPartidoService
{
    /**
     * @return array<string, true>  Claves "idDivision|equipoLocal|equipoVisitante|fecha"
     *                               (normalizadas) de los partidos ya existentes en el torneo.
     */
    public function existentesDelTorneo(int $idTorneo): array
    {
        return Partido::where('idTorneo', $idTorneo)
            ->get(['idDivision', 'equipoLocal', 'equipoVisitante', 'fechaPartido'])
            ->mapWithKeys(fn (Partido $p) => [
                $this->clave(
                    $p->idDivision,
                    $p->equipoLocal,
                    $p->equipoVisitante,
                    $p->fechaPartido?->format('Y-m-d'),
                ) => true,
            ])
            ->all();
    }

    /** @param  array<string, true>  $existentes  Salida de existentesDelTorneo(). */
    public function esPosibleDuplicado(
        array $existentes,
        ?int $idDivision,
        string $equipoLocal,
        string $equipoVisitante,
        ?string $fechaPartido,
    ): bool {
        if ($idDivision === null || $fechaPartido === null) {
            return false;
        }

        return isset($existentes[$this->clave($idDivision, $equipoLocal, $equipoVisitante, $fechaPartido)]);
    }

    private function clave(?int $idDivision, string $equipoLocal, string $equipoVisitante, ?string $fecha): string
    {
        return sprintf(
            '%s|%s|%s|%s',
            $idDivision ?? '_',
            mb_strtolower(trim($equipoLocal)),
            mb_strtolower(trim($equipoVisitante)),
            $fecha ?? '_',
        );
    }
}
