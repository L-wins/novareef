<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\CorregirMarcaRequest;
use App\Http\Requests\Academico\RegistrarScannerRequest;
use App\Models\AsistenciaAcademica;
use App\Models\SesionAcademica;
use App\Services\AsistenciaAcademicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AsistenciaController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly AsistenciaAcademicaService $asistencias,
    ) {}

    /**
     * El árbitro marca su propia asistencia (modo manual/web).
     */
    public function marcar(int $id): RedirectResponse
    {
        $arbitro    = $this->arbitroAutenticado();
        $asistencia = AsistenciaAcademica::where('idArbitro', $arbitro->idArbitro)->findOrFail($id);

        try {
            $this->asistencias->marcarWeb($asistencia);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Asistencia marcada correctamente.');
    }

    /**
     * El instructor corrige manualmente una marca antes de cerrar la sesión.
     */
    public function corregir(CorregirMarcaRequest $request, int $id): RedirectResponse
    {
        $asistencia = $this->asistenciaDelColegio($id);

        try {
            $this->asistencias->corregirMarca($asistencia, $request->validated()['estadoAsistencia']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Marca corregida.');
    }

    /**
     * Registro por scanner: lee el codigoCarnet desde la terminal del
     * instructor y responde JSON para actualizar la vista sin recargar.
     */
    public function scanner(RegistrarScannerRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $sesion = SesionAcademica::findOrFail($datos['idSesion']);
        abort_unless((int) $sesion->idColegio === $this->idColegioActivo(), 403);

        try {
            $asistencia = $this->asistencias->registrarPorScanner($sesion, $datos['codigoCarnet']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'  => true,
            'nombre'   => $asistencia->arbitro->usuario->nombreUsuario ?? '—',
            'hora'     => $asistencia->horaMarca->format('H:i:s'),
        ]);
    }

    private function asistenciaDelColegio(int $id): AsistenciaAcademica
    {
        $asistencia = AsistenciaAcademica::with('sesion')->findOrFail($id);

        abort_unless((int) $asistencia->idColegio === $this->idColegioActivo(), 403);

        return $asistencia;
    }
}
