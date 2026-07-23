<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TipoSancion;

final class TipoSancionService
{
    public function crear(int $idColegio, array $datos): TipoSancion
    {
        return TipoSancion::create([
            'idColegio'               => $idColegio,
            'etiqueta'                => $datos['etiqueta'],
            'articuloReglamento'      => $datos['articuloReglamento'] ?? null,
            'severidad'               => $datos['severidad'],
            'diasSuspensionSugeridos' => $datos['diasSuspensionSugeridos'] ?? null,
            'descripcion'             => $datos['descripcion'] ?? null,
            'esActivo'                => true,
        ]);
    }

    /**
     * @return string  Etiqueta legible del estado resultante, para el mensaje flash.
     */
    public function alternarActivo(TipoSancion $tipo): string
    {
        $tipo->update(['esActivo' => ! $tipo->esActivo]);

        return $tipo->esActivo ? 'activado' : 'desactivado';
    }

    /**
     * @throws \RuntimeException  Si el tipo ya tiene sanciones registradas.
     */
    public function eliminar(TipoSancion $tipo): void
    {
        if ($tipo->sanciones()->exists()) {
            throw new \RuntimeException('No se puede eliminar un tipo de sanción con sanciones registradas.');
        }

        $tipo->delete();
    }
}
