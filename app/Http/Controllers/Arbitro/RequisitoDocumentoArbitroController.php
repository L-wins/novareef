<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreRequisitoDocumentoArbitroRequest;
use App\Models\Arbitro;
use App\Models\CategoriaArbitro;
use App\Models\RequisitoDocumentoArbitro;
use App\Services\DocumentoArbitroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RequisitoDocumentoArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DocumentoArbitroService $documentos,
    ) {}

    public function index(): View
    {
        return view('arbitros.documentos.requisitos', [
            'requisitos' => $this->requisitosDelColegio(),
            'categorias' => $this->categoriasDelColegio(),
            'arbitros' => $this->arbitrosDelColegio(),
        ]);
    }

    public function enfocar(int $idRequisito): RedirectResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);

        return redirect($this->urlIndiceEnfocada($requisito->idRequisito));
    }

    public function store(StoreRequisitoDocumentoArbitroRequest $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validated();

        $requisito = RequisitoDocumentoArbitro::create([
            'idColegio' => $this->idColegioActivo(),
            'idCategoria' => $datos['idCategoria'] ?? null,
            'idArbitro' => $datos['idArbitro'] ?? null,
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => (int) ($datos['orden'] ?? 0),
            'obligatorio' => $request->boolean('obligatorio'),
            'requiereRevision' => $request->boolean('requiereRevision'),
            'activo' => $request->boolean('activo', true),
        ]);

        if ($request->hasFile('plantilla')) {
            $this->documentos->guardarPlantilla($requisito, $request->file('plantilla'));
        }

        return $this->respuesta($request, 'Requisito documental creado correctamente.', $requisito->idRequisito);
    }

    public function update(StoreRequisitoDocumentoArbitroRequest $request, int $idRequisito): RedirectResponse|JsonResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);
        $datos = $request->validated();

        $requisito->update([
            'nombre' => $datos['nombre'],
            'idCategoria' => $datos['idCategoria'] ?? null,
            'idArbitro' => $datos['idArbitro'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => (int) ($datos['orden'] ?? 0),
            'obligatorio' => $request->boolean('obligatorio'),
            'requiereRevision' => $request->boolean('requiereRevision'),
            'activo' => $request->boolean('activo'),
        ]);

        if ($request->hasFile('plantilla')) {
            $this->documentos->guardarPlantilla($requisito, $request->file('plantilla'));
        }

        return $this->respuesta($request, 'Requisito documental actualizado.', $requisito->idRequisito);
    }

    public function cambiarEstado(Request $request, int $idRequisito): RedirectResponse|JsonResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);
        $requisito->update(['activo' => ! $requisito->activo]);

        return $this->respuesta($request, $requisito->activo
            ? 'Requisito documental activado.'
            : 'Requisito documental pausado.', $requisito->idRequisito);
    }

    public function descargarPlantilla(int $idRequisito)
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: ! Auth::user()->can('editar-arbitros'));

        abort_unless($requisito->plantillaRuta && Storage::disk(DocumentoArbitroService::DISCO)->exists($requisito->plantillaRuta), 404);

        return Storage::disk(DocumentoArbitroService::DISCO)
            ->download($requisito->plantillaRuta, $requisito->plantillaNombreOriginal ?? $requisito->nombre);
    }

    public function destroy(Request $request, int $idRequisito): RedirectResponse|JsonResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);

        try {
            $this->documentos->eliminarRequisito($requisito);
        } catch (\RuntimeException $e) {
            return $this->respuesta($request, $e->getMessage(), $requisito->idRequisito, exito: false);
        }

        return $this->respuesta($request, 'Requisito documental eliminado.', null);
    }

    /**
     * store()/update()/cambiarEstado()/destroy() comparten la misma región
     * AJAX (lista de requisitos) — con JS (X-Requested-With) responde JSON
     * con el HTML recién renderizado, enfocando el requisito tocado (null
     * tras un destroy, ya no existe); sin JS, degrada al redirect+flash con
     * el mismo '?abrir=' de siempre. Mismo patrón que
     * DocumentoArbitroController::respuesta() / CategoriaArbitroController.
     */
    private function respuesta(Request $request, string $mensaje, ?int $abrir, bool $exito = true): RedirectResponse|JsonResponse
    {
        if (! $request->ajax()) {
            $destino = $abrir ? $this->urlIndiceEnfocada($abrir) : route('requisitos-documentos-arbitro.index');

            return redirect($destino)->with($exito ? 'success' : 'error', $mensaje);
        }

        $requisitos = $this->requisitosDelColegio();

        return response()->json([
            'success' => $exito,
            'message' => $mensaje,
            'regions' => [
                'resumen' => view('arbitros.documentos.partials.resumen-requisitos', [
                    'requisitos' => $requisitos,
                ])->render(),
                'requisitos' => view('arbitros.documentos.partials.lista-requisitos', [
                    'requisitos' => $requisitos,
                    'categorias' => $this->categoriasDelColegio(),
                    'arbitros' => $this->arbitrosDelColegio(),
                    'abrir' => $abrir,
                ])->render(),
            ],
        ], $exito ? 200 : 422);
    }

    private function requisitosDelColegio(): Collection
    {
        return RequisitoDocumentoArbitro::query()
            ->with(['categoria', 'arbitro.usuario'])
            ->withCount('documentos')
            ->where('idColegio', $this->idColegioActivo())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function categoriasDelColegio(): Collection
    {
        return CategoriaArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->where('activa', true)
            ->orderBy('nombreCategoria')
            ->get();
    }

    private function arbitrosDelColegio(): Collection
    {
        return Arbitro::query()
            ->with('usuario')
            ->where('idColegio', $this->idColegioActivo())
            ->get()
            ->sortBy(fn (Arbitro $arbitro): string => $this->normalizarParaOrdenar((string) $arbitro->usuario?->nombreUsuario))
            ->values();
    }

    /**
     * Collection::sortBy() compara strings byte a byte — con nombres en
     * español, cualquiera con tilde (Á, É, Ó...) o Ñ queda relegado al final
     * del listado en vez de ordenarse junto a su letra (ej. "Álvaro" caía
     * después de "Wilson" en vez de junto a "Alejandro"). El proyecto no
     * tiene la extensión intl (Collator) disponible, así que se normaliza a
     * mano: minúsculas + tildes/Ñ reemplazadas por su letra base, sin tocar
     * el texto real que se muestra en el select.
     */
    private function normalizarParaOrdenar(string $texto): string
    {
        return strtr(mb_strtolower($texto), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
        ]);
    }

    private function requisitoDelColegio(int $idRequisito, bool $soloActivos = true): RequisitoDocumentoArbitro
    {
        return RequisitoDocumentoArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->when($soloActivos, fn ($query) => $query->where('activo', true))
            ->findOrFail($idRequisito);
    }

    private function urlIndiceEnfocada(int $idRequisito): string
    {
        return route('requisitos-documentos-arbitro.index', ['abrir' => $idRequisito]).'#requisito-'.$idRequisito;
    }
}
