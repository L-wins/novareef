<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CategoriaArbitro;

final class CategoriaArbitroService
{
    /**
     * Crea una categoría del colegio. Las categorías creadas desde el panel
     * nunca son "por defecto" (esas solo las crea el seeder al dar de alta el colegio).
     */
    public function crear(int $idColegio, string $nombre, ?string $descripcion = null): CategoriaArbitro
    {
        return CategoriaArbitro::create([
            'idColegio' => $idColegio,
            'nombreCategoria' => trim($nombre),
            'descripcion' => $this->normalizarDescripcion($descripcion),
            'esPorDefecto' => false,
            'activa' => true,
        ]);
    }

    /**
     * Actualiza la configuración editable de una categoría.
     *
     * Las categorías base mantienen su nombre para que los seeders no creen
     * duplicados si se vuelven a ejecutar, pero sí pueden tener una descripción
     * propia del colegio.
     *
     * @throws \RuntimeException Si se intenta renombrar una categoría base.
     */
    public function actualizar(CategoriaArbitro $categoria, string $nombre, ?string $descripcion = null): void
    {
        $nombre = trim($nombre);

        if ($categoria->esPorDefecto && $nombre !== $categoria->nombreCategoria) {
            throw new \RuntimeException('Las categorías por defecto no se pueden renombrar.');
        }

        $categoria->update([
            'nombreCategoria' => $categoria->esPorDefecto ? $categoria->nombreCategoria : $nombre,
            'descripcion' => $this->normalizarDescripcion($descripcion),
        ]);
    }

    /**
     * Alterna si la categoría está activa (disponible al registrar/editar árbitros).
     *
     * @return string Etiqueta legible del estado resultante, para el mensaje flash.
     */
    public function alternarActiva(CategoriaArbitro $categoria): string
    {
        $categoria->update(['activa' => ! $categoria->activa]);

        return $categoria->activa ? 'activada' : 'desactivada';
    }

    /**
     * Elimina la categoría si no es una categoría del sistema y no tiene
     * árbitros asignados, activos o archivados.
     *
     * @throws \RuntimeException Si la categoría no puede eliminarse.
     */
    public function eliminar(CategoriaArbitro $categoria): void
    {
        if ($categoria->esPorDefecto) {
            throw new \RuntimeException('Las categorías por defecto no se pueden eliminar.');
        }

        if ($categoria->arbitros()->withTrashed()->exists()) {
            throw new \RuntimeException('No se puede eliminar una categoría con árbitros asignados.');
        }

        $categoria->delete();
    }

    private function normalizarDescripcion(?string $descripcion): ?string
    {
        if ($descripcion === null) {
            return null;
        }

        $descripcion = trim($descripcion);

        return $descripcion === '' ? null : $descripcion;
    }
}
