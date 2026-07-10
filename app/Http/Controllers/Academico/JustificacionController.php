<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\RevisarJustificacionRequest;
use App\Http\Requests\Academico\StoreJustificacionRequest;
use App\Models\AsistenciaAcademica;
use App\Models\JustificacionAcademica;
use App\Services\JustificacionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JustificacionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly JustificacionAcademicaService $justificaciones,
    ) {}

    public function create(int $idAsistencia): View
    {
        $arbitro    = $this->arbitroAutenticado();
        $asistencia = AsistenciaAcademica::with('sesion.tipo')
            ->where('idArbitro', $arbitro->idArbitro)
            ->findOrFail($idAsistencia);

        return view('academico.justificaciones.create', compact('asistencia'));
    }

    public function store(StoreJustificacionRequest $request, int $idAsistencia): RedirectResponse
    {
        $arbitro    = $this->arbitroAutenticado();
        $asistencia = AsistenciaAcademica::where('idArbitro', $arbitro->idArbitro)->findOrFail($idAsistencia);
        $datos      = $request->validated();

        if ($request->hasFile('documentoPdf')) {
            $datos['documentoPdf'] = $request->file('documentoPdf')->store('justificaciones-academicas', 'local');
        }

        try {
            $this->justificaciones->crear($asistencia, $datos);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.mis-clases')->with('success', 'Justificación enviada. Quedará pendiente de revisión.');
    }

    /**
     * Justificaciones pendientes de revisión — instructor, ejecutivo o sanciones.
     */
    public function pendientes(): View
    {
        $justificaciones = JustificacionAcademica::where('idColegio', $this->idColegioActivo())
            ->where('estadoJustificacion', JustificacionAcademica::ESTADO_PENDIENTE)
            ->with(['arbitro.usuario', 'asistencia.sesion.tipo'])
            ->orderBy('fechaLimite')
            ->paginate(20);

        return view('academico.justificaciones.pendientes', compact('justificaciones'));
    }

    public function revisar(RevisarJustificacionRequest $request, int $id): RedirectResponse
    {
        $justificacion = $this->justificacionDelColegio($id);
        $datos         = $request->validated();

        try {
            if ($datos['accion'] === 'aprobar') {
                $this->justificaciones->aprobar($justificacion, Auth::user());
            } else {
                $this->justificaciones->rechazar($justificacion, $datos['motivoRechazo'], Auth::user());
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.justificaciones.pendientes')->with('success', 'Justificación revisada.');
    }

    public function descargarDocumento(int $id)
    {
        $justificacion = $this->justificacionDelColegio($id);

        abort_if($justificacion->documentoPdf === null, 404);

        if (! Auth::user()->can('editar-academico')) {
            $arbitro = $this->arbitroAutenticado();
            abort_unless((int) $justificacion->idArbitro === $arbitro->idArbitro, 403);
        }

        return Storage::disk('local')->download($justificacion->documentoPdf);
    }

    private function justificacionDelColegio(int $id): JustificacionAcademica
    {
        $justificacion = JustificacionAcademica::with('asistencia')->findOrFail($id);

        abort_unless((int) $justificacion->idColegio === $this->idColegioActivo(), 403);

        return $justificacion;
    }
}
