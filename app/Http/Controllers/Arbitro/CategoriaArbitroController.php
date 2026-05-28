<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Controller;
use App\Models\CategoriaArbitro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoriaArbitroController extends Controller
{
    public function index(): View
    {
        $idColegio  = $this->idColegioActivo();
        $categorias = CategoriaArbitro::where('idColegio', $idColegio)
            ->orderByDesc('esPorDefecto')
            ->orderBy('nombreCategoria')
            ->get();

        return view('arbitros.categorias', compact('categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();

        $request->validate([
            'nombreCategoria' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) use ($idColegio): void {
                    $exists = CategoriaArbitro::where('idColegio', $idColegio)
                        ->where('nombreCategoria', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Ya existe una categoría con ese nombre en este colegio.');
                    }
                },
            ],
        ], [
            'nombreCategoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombreCategoria.max'      => 'El nombre no puede superar 50 caracteres.',
        ]);

        CategoriaArbitro::create([
            'idColegio'       => $idColegio,
            'nombreCategoria' => $request->input('nombreCategoria'),
            'esPorDefecto'    => false,
            'activa'          => true,
        ]);

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function toggleActiva(int $id): RedirectResponse
    {
        $idColegio  = $this->idColegioActivo();
        $categoria  = CategoriaArbitro::findOrFail($id);

        abort_unless((int) $categoria->idColegio === $idColegio, 403);

        $categoria->activa = ! $categoria->activa;
        $categoria->save();

        $estado = $categoria->activa ? 'activada' : 'desactivada';

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', "Categoría {$estado} correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();
        $categoria = CategoriaArbitro::withCount('arbitros')->findOrFail($id);

        abort_unless((int) $categoria->idColegio === $idColegio, 403);

        if ($categoria->esPorDefecto) {
            return redirect()
                ->route('categorias.arbitro.index')
                ->with('error', 'Las categorías por defecto no se pueden eliminar.');
        }

        if ($categoria->arbitros_count > 0) {
            return redirect()
                ->route('categorias.arbitro.index')
                ->with('error', 'No se puede eliminar una categoría con árbitros asignados.');
        }

        $categoria->delete();

        return redirect()
            ->route('categorias.arbitro.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    private function idColegioActivo(): int
    {
        $idColegio = Auth::user()?->idColegio;
        abort_if($idColegio === null, 403);

        return (int) $idColegio;
    }
}
