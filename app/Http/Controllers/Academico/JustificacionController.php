<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreJustificacionRequest;
use App\Models\AsistenciaAcademica;
use App\Services\JustificacionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Solo la mitad "árbitro" del flujo de justificaciones (crear/enviar). La
 * revisión (aprobar/rechazar) vive en Sancion\JustificacionRevisionController.
 */
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
}
