<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreCategoriaArbitroRequest;
use App\Models\CategoriaArbitro;
use App\Services\CategoriaArbitroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoriaArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly CategoriaArbitroService $categorias,
    ) {}

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
        $this->categorias->crear($this->idColegioActivo(), $request->validated('nombreCategoria'));

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function toggleActiva(int $id): RedirectResponse
    {
        $categoria = $this->categoriaDelColegio($id);
        $estado    = $this->categorias->alternarActiva($categoria);

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', "Categoría {$estado} correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $categoria = $this->categoriaDelColegio($id);

        try {
            $this->categorias->eliminar($categoria);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Resuelve una categoría por ID verificando que pertenezca al colegio activo.
     * Centraliza lo que antes se repetía en toggleActiva() y destroy().
     */
    private function categoriaDelColegio(int $id): CategoriaArbitro
    {
        $categoria = CategoriaArbitro::findOrFail($id);

        abort_unless((int) $categoria->idColegio === $this->idColegioActivo(), 403);

        return $categoria;
    }
}
