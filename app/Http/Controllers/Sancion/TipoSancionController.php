<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sancion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sancion\StoreTipoSancionRequest;
use App\Models\TipoSancion;
use App\Services\TipoSancionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoSancionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly TipoSancionService $tipos,
    ) {}

    public function index(): View
    {
        $tipos = TipoSancion::where('idColegio', $this->idColegioActivo())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('sanciones.tipos', compact('tipos'));
    }

    public function store(StoreTipoSancionRequest $request): RedirectResponse
    {
        $this->tipos->crear($this->idColegioActivo(), $request->validated());

        return redirect()
            ->route('tipos-sancion.index')
            ->with('success', 'Tipo de sanción creado correctamente.');
    }

    public function toggleActivo(int $id): RedirectResponse
    {
        $tipo   = $this->tipoDelColegio($id);
        $estado = $this->tipos->alternarActivo($tipo);

        return redirect()
            ->route('tipos-sancion.index')
            ->with('success', "Tipo de sanción {$estado} correctamente.");
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
            ->route('tipos-sancion.index')
            ->with('success', 'Tipo de sanción eliminado correctamente.');
    }

    private function tipoDelColegio(int $id): TipoSancion
    {
        $tipo = TipoSancion::findOrFail($id);

        abort_unless((int) $tipo->idColegio === $this->idColegioActivo(), 403);

        return $tipo;
    }
}
