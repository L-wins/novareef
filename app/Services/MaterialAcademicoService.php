<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaterialAcademico;
use App\Models\SesionAcademica;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class MaterialAcademicoService
{
    /**
     * Sube un material de clase — se puede adjuntar antes, durante o
     * después de la sesión, sin depender de su estado. Queda disponible
     * para todos los árbitros del colegio con acceso al módulo académico.
     */
    public function subir(SesionAcademica $sesion, UploadedFile $archivo, string $titulo, ?User $usuario): MaterialAcademico
    {
        $ruta = $archivo->store('materiales-academicos', 'local');

        return MaterialAcademico::create([
            'idColegio'      => $sesion->idColegio,
            'idSesion'       => $sesion->idSesion,
            'titulo'         => $titulo,
            'archivo'        => $ruta,
            'extension'      => strtolower($archivo->getClientOriginalExtension()),
            'tamanoBytes'    => $archivo->getSize(),
            'idUsuarioSubio' => $usuario?->idUsuario,
        ]);
    }

    public function eliminar(MaterialAcademico $material): void
    {
        Storage::disk('local')->delete($material->archivo);
        $material->delete();
    }
}
