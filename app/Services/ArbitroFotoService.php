<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ArbitroFotoService
{
    private const DISCO      = 'public';
    private const DIRECTORIO = 'fotos-arbitros';

    /**
     * Reemplaza la foto de perfil del árbitro: borra la anterior (si existe)
     * antes de almacenar la nueva, para no dejar archivos huérfanos en disco.
     */
    public function actualizar(Arbitro $arbitro, UploadedFile $foto): void
    {
        $this->eliminarArchivo($arbitro);

        $ruta = $foto->store(self::DIRECTORIO, self::DISCO);

        $arbitro->update(['fotoPerfil' => $ruta]);
    }

    /**
     * Elimina la foto de perfil del árbitro: el archivo en disco y su referencia en BD.
     */
    public function eliminar(Arbitro $arbitro): void
    {
        $this->eliminarArchivo($arbitro);

        $arbitro->update(['fotoPerfil' => null]);
    }

    private function eliminarArchivo(Arbitro $arbitro): void
    {
        if ($arbitro->fotoPerfil && Storage::disk(self::DISCO)->exists($arbitro->fotoPerfil)) {
            Storage::disk(self::DISCO)->delete($arbitro->fotoPerfil);
        }
    }
}
