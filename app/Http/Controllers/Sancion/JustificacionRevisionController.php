<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sancion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sancion\RevisarJustificacionRequest;
use App\Models\JustificacionAcademica;
use App\Services\JustificacionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Revisión de justificaciones de inasistencia académica — vive en Sanciones
 * (no en Académico) porque quien revisa es indistintamente instructor,
 * ejecutivo o el rol sanciones, y este último trabaja principalmente desde
 * esta sección. El modelo/servicio siguen en Académico: la justificación es
 * conceptualmente parte de la asistencia a una sesión, no de una sanción.
 * Ver JustificacionController (Academico) para create/store — eso sigue
 * siendo del árbitro, no se mueve.
 */
class JustificacionRevisionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly JustificacionAcademicaService $justificaciones,
    ) {}

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

        return view('sanciones.justificaciones-pendientes', compact('justificaciones'));
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

        return redirect()->route('sanciones.justificaciones.pendientes')->with('success', 'Justificación revisada.');
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
