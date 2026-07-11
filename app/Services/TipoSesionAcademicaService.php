<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TipoSesionAcademica;

final class TipoSesionAcademicaService
{
    public function crear(int $idColegio, array $datos): TipoSesionAcademica
    {
        return TipoSesionAcademica::create([
            'idColegio'   => $idColegio,
            'etiqueta'    => $datos['etiqueta'],
            'esOficial'   => $datos['esOficial'] ?? false,
            'descripcion' => $datos['descripcion'] ?? null,
            'esActivo'    => true,
        ]);
    }

    /**
     * @return string  Etiqueta legible del estado resultante, para el mensaje flash.
     */
    public function alternarActivo(TipoSesionAcademica $tipo): string
    {
        $tipo->update(['esActivo' => ! $tipo->esActivo]);

        return $tipo->esActivo ? 'activado' : 'desactivado';
    }

    /**
     * @throws \RuntimeException  Si el tipo ya tiene sesiones registradas.
     */
    public function eliminar(TipoSesionAcademica $tipo): void
    {
        if ($tipo->sesiones()->exists()) {
            throw new \RuntimeException('No se puede eliminar un tipo de sesión con sesiones registradas.');
        }

        $tipo->delete();
    }
}
