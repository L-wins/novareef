<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreCategoriaArbitroRequest;
use App\Http\Requests\Arbitro\UpdateCategoriaArbitroRequest;
use App\Models\CategoriaArbitro;
use App\Services\CategoriaArbitroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CategoriaArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly CategoriaArbitroService $categorias,
    ) {}

    public function index(): View
    {
        $categorias = $this->categoriasDelColegio();

        return view('arbitros.categorias', [
            'categorias' => $categorias,
            'resumen' => $this->resumen($categorias),
        ]);
    }

    public function store(StoreCategoriaArbitroRequest $request): RedirectResponse|JsonResponse
    {
        $categoria = $this->categorias->crear(
            $this->idColegioActivo(),
            $request->validated('nombreCategoria'),
            $request->validated('descripcion'),
        );

        return $this->respuesta($request, 'Categoría creada correctamente.', $categoria->idCategoria);
    }

    public function update(UpdateCategoriaArbitroRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $categoria = $this->categoriaDelColegio($id);

        try {
            $this->categorias->actualizar(
                $categoria,
                $request->validated('nombreCategoria'),
                $request->validated('descripcion'),
            );
        } catch (\RuntimeException $e) {
            return $this->respuesta($request, $e->getMessage(), $categoria->idCategoria, exito: false);
        }

        return $this->respuesta($request, 'Categoría actualizada correctamente.', $categoria->idCategoria);
    }

    public function cambiarEstado(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $categoria = $this->categoriaDelColegio($id);
        $estado = $this->categorias->alternarActiva($categoria);

        return $this->respuesta($request, "Categoría {$estado} correctamente.", $categoria->idCategoria);
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $categoria = $this->categoriaDelColegio($id);

        try {
            $this->categorias->eliminar($categoria);
        } catch (\RuntimeException $e) {
            return $this->respuesta($request, $e->getMessage(), $categoria->idCategoria, exito: false);
        }

        return $this->respuesta($request, 'Categoría eliminada correctamente.', null);
    }

    // ── Helpers privados ──────────────────

    /**
     * store()/update()/cambiarEstado()/destroy() comparten la misma región
     * AJAX (lista de categorías) — con JS (X-Requested-With) responde JSON
     * con el HTML recién renderizado, enfocando la categoría tocada (null
     * tras un destroy, ya no existe); sin JS, degrada al redirect+flash de
     * siempre. Mismo patrón que Requisito/DocumentoArbitroController.
     */
    private function respuesta(Request $request, string $mensaje, ?int $abrir, bool $exito = true): RedirectResponse|JsonResponse
    {
        if (! $request->ajax()) {
            $destino = $abrir ? route('categorias.arbitro.index', ['abrir' => $abrir]) : route('categorias.arbitro.index');

            return redirect($destino)->with($exito ? 'success' : 'error', $mensaje);
        }

        $categorias = $this->categoriasDelColegio();

        return response()->json([
            'success' => $exito,
            'message' => $mensaje,
            'regions' => [
                'resumen' => view('arbitros.partials.resumen-categorias', [
                    'resumen' => $this->resumen($categorias),
                ])->render(),
                'categorias' => view('arbitros.partials.lista-categorias', [
                    'categorias' => $categorias,
                    'abrir' => $abrir,
                ])->render(),
            ],
        ], $exito ? 200 : 422);
    }

    /**
     * Resuelve una categoría por ID verificando que pertenezca al colegio activo.
     * Centraliza lo que antes se repetía en cambiarEstado() y destroy(). Igual
     * que ArbitroController::arbitroDelColegio(): el scope va en la query
     * (404 si es de otro colegio), no un abort_unless(403) después de un
     * findOrFail() sin scope — eso revelaría a un colegio ajeno que el
     * recurso existe.
     */
    private function categoriaDelColegio(int $id): CategoriaArbitro
    {
        return CategoriaArbitro::where('idColegio', $this->idColegioActivo())
            ->findOrFail($id);
    }

    private function categoriasDelColegio(): Collection
    {
        return CategoriaArbitro::where('idColegio', $this->idColegioActivo())
            ->withCount([
                'arbitros',
                'arbitros as arbitros_total_count' => fn ($query) => $query->withTrashed(),
            ])
            ->orderByDesc('esPorDefecto')
            ->orderBy('nombreCategoria')
            ->get();
    }

    /**
     * @param  Collection<int, CategoriaArbitro>  $categorias
     * @return array<string, int>
     */
    private function resumen(Collection $categorias): array
    {
        return [
            'total' => $categorias->count(),
            'activas' => $categorias->where('activa', true)->count(),
            'personalizadas' => $categorias->where('esPorDefecto', false)->count(),
            'arbitrosAsignados' => (int) $categorias->sum('arbitros_count'),
        ];
    }
}
