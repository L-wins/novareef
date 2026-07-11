<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreMaterialAcademicoRequest;
use App\Models\MaterialAcademico;
use App\Models\SesionAcademica;
use App\Services\MaterialAcademicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaterialAcademicoController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly MaterialAcademicoService $materiales,
    ) {}

    /**
     * Sube material de clase — se puede hacer antes, durante o después de
     * la sesión, sin importar su estado.
     */
    public function store(StoreMaterialAcademicoRequest $request, int $idSesion): RedirectResponse
    {
        $sesion = SesionAcademica::findOrFail($idSesion);
        abort_unless((int) $sesion->idColegio === $this->idColegioActivo(), 403);

        $this->materiales->subir($sesion, $request->file('archivo'), $request->validated()['titulo'], Auth::user());

        return back()->with('success', 'Material subido correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $material = $this->materialDelColegio($id);
        $this->materiales->eliminar($material);

        return back()->with('success', 'Material eliminado.');
    }

    /**
     * Descarga disponible para cualquier usuario del colegio con acceso al
     * módulo académico — el material es visible para todos, no solo para
     * quienes aplican a esa sesión puntual.
     */
    public function descargar(int $id)
    {
        $material = $this->materialDelColegio($id);

        return Storage::disk('local')->download($material->archivo, $material->titulo . '.' . $material->extension);
    }

    private function materialDelColegio(int $id): MaterialAcademico
    {
        $material = MaterialAcademico::findOrFail($id);

        abort_unless((int) $material->idColegio === $this->idColegioActivo(), 403);

        return $material;
    }
}
