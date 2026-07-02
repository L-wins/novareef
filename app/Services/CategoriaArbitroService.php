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
    public function crear(int $idColegio, string $nombre): CategoriaArbitro
    {
        return CategoriaArbitro::create([
            'idColegio'       => $idColegio,
            'nombreCategoria' => $nombre,
            'esPorDefecto'    => false,
            'activa'          => true,
        ]);
    }

    /**
     * Alterna si la categoría está activa (disponible al registrar/editar árbitros).
     *
     * @return string  Etiqueta legible del estado resultante, para el mensaje flash.
     */
    public function alternarActiva(CategoriaArbitro $categoria): string
    {
        $categoria->update(['activa' => ! $categoria->activa]);

        return $categoria->activa ? 'activada' : 'desactivada';
    }

    /**
     * Elimina la categoría si no es una categoría del sistema y no tiene
     * árbitros asignados actualmente.
     *
     * @throws \RuntimeException  Si la categoría no puede eliminarse.
     */
    public function eliminar(CategoriaArbitro $categoria): void
    {
        if ($categoria->esPorDefecto) {
            throw new \RuntimeException('Las categorías por defecto no se pueden eliminar.');
        }

        if ($categoria->arbitros()->exists()) {
            throw new \RuntimeException('No se puede eliminar una categoría con árbitros asignados.');
        }

        $categoria->delete();
    }
}
