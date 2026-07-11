<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreTipoSesionRequest;
use App\Models\TipoSesionAcademica;
use App\Services\TipoSesionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoSesionAcademicaController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly TipoSesionAcademicaService $tipos,
    ) {}

    public function index(): View
    {
        $tipos = TipoSesionAcademica::where('idColegio', $this->idColegioActivo())
            ->orderBy('orden')
            ->orderBy('etiqueta')
            ->get();

        return view('academico.tipos', compact('tipos'));
    }

    public function store(StoreTipoSesionRequest $request): RedirectResponse
    {
        $this->tipos->crear($this->idColegioActivo(), $request->validated());

        return redirect()
            ->route('tipos-sesion-academica.index')
            ->with('success', 'Tipo de sesión creado correctamente.');
    }

    public function toggleActivo(int $id): RedirectResponse
    {
        $tipo   = $this->tipoDelColegio($id);
        $estado = $this->tipos->alternarActivo($tipo);

        return redirect()
            ->route('tipos-sesion-academica.index')
            ->with('success', "Tipo de sesión {$estado} correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $tipo = $this->tipoDelColegio($id);

        try {
            $this->tipos->eliminar($tipo);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tipos-sesion-academica.index')
            ->with('success', 'Tipo de sesión eliminado correctamente.');
    }

    private function tipoDelColegio(int $id): TipoSesionAcademica
    {
        $tipo = TipoSesionAcademica::findOrFail($id);

        abort_unless((int) $tipo->idColegio === $this->idColegioActivo(), 403);

        return $tipo;
    }
}
