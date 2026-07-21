<?php

declare(strict_types=1);

namespace App\Services\Importacion;

use App\Models\Arbitro;
use App\Models\DivisionTorneo;
use App\Models\SedeTorneo;
use Illuminate\Support\Str;

final class MatchingTextoService
{
    /**
     * Umbral de similitud (0-100, ver similar_text()) a partir del cual una
     * sugerencia difusa se considera razonable para mostrarse al usuario.
     * Por debajo de esto el ruido de falsos positivos supera la utilidad
     * (ej. "Primera C" vs "Primera B" comparten mucho texto pero son
     * divisiones distintas — el umbral evita sugerir ese tipo de casos).
     */
    private const UMBRAL_SUGERENCIA = 70.0;

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
     * Árbitros designables del colegio, para matchear por nombre los roles
     * que trae el Word (ARBITRO/LINEA UNO/LINEA DOS/EMERGENTE). Solo los que
     * pueden ser designados (excluye suspendido/retirado/proceso_ingreso) —
     * mismo criterio que ya usa CandidatosDesignacionService.
     *
     * @return array<int, array{id: int, nombre: string}>
     */
    public function arbitrosDelColegio(int $idColegio): array
    {
        return Arbitro::where('idColegio', $idColegio)
            ->whereNotIn('estadoArbitro', ['proceso_ingreso', 'suspendido', 'retirado'])
            ->with('usuario:idUsuario,nombreUsuario')
            ->get(['idArbitro', 'idUsuario'])
            ->filter(fn (Arbitro $a) => $a->usuario !== null)
            ->map(fn (Arbitro $a) => ['id' => $a->idArbitro, 'nombre' => $a->usuario->nombreUsuario])
            ->values()
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

    /**
     * Igual que matchear(), pero si no hay coincidencia exacta busca el
     * candidato más parecido por similitud de texto (typos, orden de
     * palabras, abreviaturas leves) y lo devuelve como sugerencia — nunca se
     * autoasigna, el usuario la confirma o la descarta en el preview.
     *
     * @param  array<int, array{id: int, nombre: string}>  $candidatos
     * @return array{idMatch: ?int, sugerencia: ?array{id: int, nombre: string, similitud: float}}
     */
    public function matchearConSugerencia(string $textoBuscado, array $candidatos): array
    {
        $idExacto = $this->matchear($textoBuscado, $candidatos);
        if ($idExacto !== null) {
            return ['idMatch' => $idExacto, 'sugerencia' => null];
        }

        if (trim($textoBuscado) === '' || $candidatos === []) {
            return ['idMatch' => null, 'sugerencia' => null];
        }

        $normalizado = $this->normalizar($textoBuscado);
        $mejor       = null;
        $mejorPct    = 0.0;

        foreach ($candidatos as $candidato) {
            similar_text($normalizado, $this->normalizar($candidato['nombre']), $porcentaje);
            if ($porcentaje > $mejorPct) {
                $mejorPct = $porcentaje;
                $mejor    = $candidato;
            }
        }

        if ($mejor === null || $mejorPct < self::UMBRAL_SUGERENCIA) {
            return ['idMatch' => null, 'sugerencia' => null];
        }

        return [
            'idMatch'    => null,
            'sugerencia' => [
                'id'        => $mejor['id'],
                'nombre'    => $mejor['nombre'],
                'similitud' => round($mejorPct, 1),
            ],
        ];
    }
}
