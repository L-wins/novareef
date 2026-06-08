<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreCategoriaArbitroRequest;
use App\Models\CategoriaArbitro;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoriaArbitroController extends Controller
{
    use ResuelveColegio;

    public function index(): View
    {
        $categorias = CategoriaArbitro::where('idColegio', $this->idColegioActivo())
            ->orderByDesc('esPorDefecto')
            ->orderBy('nombreCategoria')
            ->get();

        return view('arbitros.categorias', compact('categorias'));
    }

    public function store(StoreCategoriaArbitroRequest $request): RedirectResponse
    {
        CategoriaArbitro::create([
            'idColegio'       => $this->idColegioActivo(),
            'nombreCategoria' => $request->validated('nombreCategoria'),
            'esPorDefecto'    => false,
            'activa'          => true,
        ]);

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function toggleActiva(int $id): RedirectResponse
    {
        $categoria = CategoriaArbitro::findOrFail($id);

        abort_unless((int) $categoria->idColegio === $this->idColegioActivo(), 403);

        $categoria->update(['activa' => ! $categoria->activa]);

        $estado = $categoria->activa ? 'activada' : 'desactivada';

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', "Categoría {$estado} correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $categoria = CategoriaArbitro::withCount('arbitros')->findOrFail($id);

        abort_unless((int) $categoria->idColegio === $this->idColegioActivo(), 403);

        if ($categoria->esPorDefecto) {
            return back()->with('error', 'Las categorías por defecto no se pueden eliminar.');
        }

        if ($categoria->arbitros_count > 0) {
            return back()->with('error', 'No se puede eliminar una categoría con árbitros asignados.');
        }

        $categoria->delete();

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
