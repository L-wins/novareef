<?php

declare(strict_types=1);

namespace App\Http\Controllers\Arbitro;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arbitro\DevolverDocumentoArbitroRequest;
use App\Http\Requests\Arbitro\StoreDocumentoArbitroRequest;
use App\Models\Arbitro;
use App\Models\DocumentoArbitro;
use App\Models\RequisitoDocumentoArbitro;
use App\Services\DocumentoArbitroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentoArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DocumentoArbitroService $documentos,
    ) {}

    public function store(StoreDocumentoArbitroRequest $request, int $idArbitro, int $idRequisito): RedirectResponse
    {
        $arbitro = $this->arbitroAccesible($idArbitro, permiteStaff: true);
        $requisito = RequisitoDocumentoArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->where('activo', true)
            ->findOrFail($idRequisito);

        try {
            $documento = $this->documentos->guardarEntrega($arbitro, $requisito, $request->file('archivo'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $documento->estadoRevision === DocumentoArbitro::ESTADO_APROBADO
            ? 'Documento cargado y aprobado automaticamente.'
            : 'Documento enviado para revision.');
    }

    public function descargar(int $idDocumento)
    {
        $documento = $this->documentoAccesible($idDocumento);

        abort_unless(Storage::disk(DocumentoArbitroService::DISCO)->exists($documento->archivoRuta), 404);

        return Storage::disk(DocumentoArbitroService::DISCO)
            ->download($documento->archivoRuta, $documento->nombreOriginal ?? $documento->nombreDocumento);
    }

    public function aprobar(int $idDocumento): RedirectResponse
    {
        $documento = $this->documentoAccesible($idDocumento, soloStaff: true);
        $this->documentos->aprobar($documento, Auth::user());

        return back()->with('success', 'Documento aprobado.');
    }

    public function devolver(DevolverDocumentoArbitroRequest $request, int $idDocumento): RedirectResponse
    {
        $documento = $this->documentoAccesible($idDocumento, soloStaff: true);
        $this->documentos->devolver($documento, $request->validated('comentarioRevision'), Auth::user());

        return back()->with('success', 'Documento devuelto para correccion.');
    }

    private function arbitroAccesible(int $idArbitro, bool $permiteStaff = false): Arbitro
    {
        $query = Arbitro::query()
            ->with(['usuario'])
            ->where('idColegio', $this->idColegioActivo())
            ->where('idArbitro', $idArbitro);

        if (! ($permiteStaff && Auth::user()->can('editar-arbitros'))) {
            $query->where('idUsuario', Auth::id());
        }

        return $query->firstOrFail();
    }

    private function documentoAccesible(int $idDocumento, bool $soloStaff = false): DocumentoArbitro
    {
        $documento = DocumentoArbitro::query()
            ->with(['arbitro.usuario', 'requisito', 'revisor'])
            ->whereHas('arbitro', fn ($query) => $query->where('idColegio', $this->idColegioActivo()))
            ->findOrFail($idDocumento);

        if ($soloStaff) {
            abort_unless(Auth::user()->can('editar-arbitros'), 403);
        } elseif (! Auth::user()->can('ver-arbitros')) {
            abort_unless((int) $documento->arbitro->idUsuario === (int) Auth::id(), 403);
        }

        return $documento;
    }
}
