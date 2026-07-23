<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\StoreRequisitoDocumentoArbitroRequest;
use App\Models\CategoriaArbitro;
use App\Models\RequisitoDocumentoArbitro;
use App\Services\DocumentoArbitroService;
use Illuminate\Http\RedirectResponse;
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
        $requisitos = RequisitoDocumentoArbitro::query()
            ->with(['categoria'])
            ->withCount('documentos')
            ->where('idColegio', $this->idColegioActivo())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        $categorias = CategoriaArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->where('activa', true)
            ->orderBy('nombreCategoria')
            ->get();

        return view('arbitros.documentos.requisitos', compact('requisitos', 'categorias'));
    }

    public function store(StoreRequisitoDocumentoArbitroRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $requisito = RequisitoDocumentoArbitro::create([
            'idColegio' => $this->idColegioActivo(),
            'idCategoria' => $datos['idCategoria'] ?? null,
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

        return back()->with('success', 'Requisito documental creado correctamente.');
    }

    public function update(StoreRequisitoDocumentoArbitroRequest $request, int $idRequisito): RedirectResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);
        $datos = $request->validated();

        $requisito->update([
            'nombre' => $datos['nombre'],
            'idCategoria' => $datos['idCategoria'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'orden' => (int) ($datos['orden'] ?? 0),
            'obligatorio' => $request->boolean('obligatorio'),
            'requiereRevision' => $request->boolean('requiereRevision'),
            'activo' => $request->boolean('activo'),
        ]);

        if ($request->hasFile('plantilla')) {
            $this->documentos->guardarPlantilla($requisito, $request->file('plantilla'));
        }

        return back()->with('success', 'Requisito documental actualizado.');
    }

    public function cambiarEstado(int $idRequisito): RedirectResponse
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: false);
        $requisito->update(['activo' => ! $requisito->activo]);

        return back()->with('success', $requisito->activo
            ? 'Requisito documental activado.'
            : 'Requisito documental pausado.');
    }

    public function descargarPlantilla(int $idRequisito)
    {
        $requisito = $this->requisitoDelColegio($idRequisito, soloActivos: ! Auth::user()->can('editar-arbitros'));

        abort_unless($requisito->plantillaRuta && Storage::disk(DocumentoArbitroService::DISCO)->exists($requisito->plantillaRuta), 404);

        return Storage::disk(DocumentoArbitroService::DISCO)
            ->download($requisito->plantillaRuta, $requisito->plantillaNombreOriginal ?? $requisito->nombre);
    }

    private function requisitoDelColegio(int $idRequisito, bool $soloActivos = true): RequisitoDocumentoArbitro
    {
        return RequisitoDocumentoArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->when($soloActivos, fn ($query) => $query->where('activo', true))
            ->findOrFail($idRequisito);
    }
}
