<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Colegio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ColegioLogoService
{
    private const DISCO      = 'public';
    private const DIRECTORIO = 'logos-colegios';

    /**
     * Reemplaza el logo del colegio: borra el archivo anterior (si existe)
     * antes de almacenar el nuevo, para no dejar huérfanos en disco.
     */
    public function actualizar(Colegio $colegio, UploadedFile $logo): void
    {
        $this->eliminarArchivo($colegio);

        $ruta = $logo->store(self::DIRECTORIO, self::DISCO);

        $colegio->update(['logoColegio' => $ruta]);
    }

    /** Elimina el logo del colegio: el archivo en disco y su referencia en BD. */
    public function eliminar(Colegio $colegio): void
    {
        $this->eliminarArchivo($colegio);

        $colegio->update(['logoColegio' => null]);
    }

    /**
     * logoColegio también admite URLs externas (cargadas por el superadmin al
     * crear el colegio) — esas no tienen archivo local que borrar.
     */
    private function eliminarArchivo(Colegio $colegio): void
    {
        $ruta = $colegio->logoColegio;

        if ($ruta && ! Str::startsWith($ruta, ['http://', 'https://']) && Storage::disk(self::DISCO)->exists($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }
}
