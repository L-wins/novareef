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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentoArbitroController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DocumentoArbitroService $documentos,
    ) {}

    public function store(StoreDocumentoArbitroRequest $request, int $idArbitro, int $idRequisito): RedirectResponse|JsonResponse
    {
        $arbitro = $this->arbitroAccesible($idArbitro, permiteStaff: true);
        $requisito = RequisitoDocumentoArbitro::query()
            ->where('idColegio', $this->idColegioActivo())
            ->where('activo', true)
            ->findOrFail($idRequisito);

        try {
            $documento = $this->documentos->guardarEntrega($arbitro, $requisito, $request->file('archivo'));
        } catch (RuntimeException $e) {
            return $this->respuesta($request, $arbitro, false, $e->getMessage());
        }

        return $this->respuesta($request, $arbitro, true, $documento->estadoRevision === DocumentoArbitro::ESTADO_APROBADO
            ? 'Documento cargado y aprobado automáticamente.'
            : 'Documento enviado para revisión.');
    }

    public function descargar(int $idDocumento)
    {
        $documento = $this->documentoAccesible($idDocumento);

        abort_unless(Storage::disk(DocumentoArbitroService::DISCO)->exists($documento->archivoRuta), 404);

        return Storage::disk(DocumentoArbitroService::DISCO)
            ->download($documento->archivoRuta, $documento->nombreOriginal ?? $documento->nombreDocumento);
    }

    public function aprobar(Request $request, int $idDocumento): RedirectResponse|JsonResponse
    {
        $documento = $this->documentoAccesible($idDocumento, soloStaff: true);
        $this->documentos->aprobar($documento, Auth::user());

        return $this->respuesta($request, $documento->arbitro, true, 'Documento aprobado.');
    }

    public function devolver(DevolverDocumentoArbitroRequest $request, int $idDocumento): RedirectResponse|JsonResponse
    {
        $documento = $this->documentoAccesible($idDocumento, soloStaff: true);
        $this->documentos->devolver($documento, $request->validated('comentarioRevision'), Auth::user());

        return $this->respuesta($request, $documento->arbitro, true, 'Documento devuelto para corrección.');
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

    /**
     * El documento puede contener datos personales sensibles (cédula,
     * certificados médicos) — a diferencia del resto del módulo de árbitros,
     * "poder ver la lista de árbitros" (ver-arbitros, que tesorero/designador/
     * sanciones/tecnico también tienen) no es motivo suficiente para
     * descargarlo. Solo puede verlo quien puede revisarlo (editar-arbitros,
     * mismo permiso que aprobar()/devolver()) o el propio árbitro dueño.
     */
    private function documentoAccesible(int $idDocumento, bool $soloStaff = false): DocumentoArbitro
    {
        $documento = DocumentoArbitro::query()
            ->with(['arbitro.usuario', 'requisito', 'revisor'])
            ->whereHas('arbitro', fn ($query) => $query->where('idColegio', $this->idColegioActivo()))
            ->findOrFail($idDocumento);

        $puedeRevisar = Auth::user()->can('editar-arbitros');

        if ($soloStaff) {
            abort_unless($puedeRevisar, 403);
        } elseif (! $puedeRevisar) {
            abort_unless((int) $documento->arbitro->idUsuario === (int) Auth::id(), 403);
        }

        return $documento;
    }

    /**
     * store()/aprobar()/devolver() comparten la misma región AJAX
     * (documentos-panel-contenido) — con JS (X-Requested-With) responde JSON
     * con el HTML recién renderizado; sin JS, degrada al redirect+flash de
     * siempre. Mismo patrón que EstadisticasController con sus sub-acciones.
     */
    private function respuesta(Request $request, Arbitro $arbitro, bool $exito, string $mensaje): RedirectResponse|JsonResponse
    {
        if (! $request->ajax()) {
            return back()->with($exito ? 'success' : 'error', $mensaje);
        }

        $modoRevision = Auth::user()->can('editar-arbitros');
        $documentosRequisitos = $this->documentos->panelParaArbitro($arbitro->fresh(['usuario', 'documentos.requisito', 'documentos.revisor']));

        return response()->json([
            'success' => $exito,
            'message' => $mensaje,
            'regions' => [
                'documentos' => view('arbitros.partials.documentos-panel-contenido', [
                    'arbitro' => $arbitro,
                    'modoRevision' => $modoRevision,
                    'documentosRequisitos' => $documentosRequisitos,
                    'documentosResumen' => $this->documentos->resumenParaArbitro($arbitro, $documentosRequisitos),
                ])->render(),
            ],
        ], $exito ? 200 : 422);
    }
}
