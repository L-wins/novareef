<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\DivisionTorneo;
use App\Models\SedeTorneo;
use Illuminate\Support\Str;

final class MatchingTextoService
{
    public function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        return Str::of($texto)->ascii()->toString();
    }

    /** @return array<int, array{id: int, nombre: string}> */
    public function divisionesDelTorneo(int $idTorneo): array
    {
        return DivisionTorneo::where('idTorneo', $idTorneo)
            ->get(['idDivision', 'nombreDivision'])
            ->map(fn ($d) => ['id' => $d->idDivision, 'nombre' => $d->nombreDivision])
            ->all();
    }

    /** @return array<int, array{id: int, nombre: string}> */
    public function sedesDelTorneo(int $idTorneo): array
    {
        return SedeTorneo::where('idTorneo', $idTorneo)
            ->get(['idSede', 'nombreSede'])
            ->map(fn ($s) => ['id' => $s->idSede, 'nombre' => $s->nombreSede])
            ->all();
    }

    /**
     * @param  array<int, array{id: int, nombre: string}>  $candidatos
     */
    public function matchear(string $textoBuscado, array $candidatos): ?int
    {
        $normalizado = $this->normalizar($textoBuscado);

        foreach ($candidatos as $candidato) {
            if ($this->normalizar($candidato['nombre']) === $normalizado) {
                return $candidato['id'];
            }
        }

        return null;
    }
}
