<?php

declare(strict_types=1);

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\StoreSesionAcademicaRequest;
use App\Http\Requests\Academico\UpdateSesionAcademicaRequest;
use App\Models\CategoriaArbitro;
use App\Models\SesionAcademica;
use App\Models\TipoSesionAcademica;
use App\Services\SesionAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SesionAcademicaController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly SesionAcademicaService $sesiones,
    ) {}

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        $sesiones = SesionAcademica::where('idColegio', $idColegio)
            ->with('tipo')
            ->withCount([
                'asistencias',
                'asistencias as presentes_count' => fn ($q) => $q->where('estadoAsistencia', 'presente'),
            ])
            ->orderByDesc('fechaSesion')
            ->paginate(20)
            ->withQueryString();

        return view('academico.sesiones.index', compact('sesiones'));
    }

    public function create(): View
    {
        $idColegio = $this->idColegioActivo();

        $tipos      = TipoSesionAcademica::where('idColegio', $idColegio)->where('esActivo', true)->orderBy('orden')->orderBy('etiqueta')->get();
        $categorias = CategoriaArbitro::where('idColegio', $idColegio)->where('activa', true)->orderBy('nombreCategoria')->get();

        return view('academico.sesiones.create', compact('tipos', 'categorias'));
    }

    public function store(StoreSesionAcademicaRequest $request): RedirectResponse
    {
        $sesion = $this->sesiones->crearSesion($this->idColegioActivo(), $request->validated(), Auth::user());

        return redirect()
            ->route('academico.sesiones.show', $sesion->idSesion)
            ->with('success', 'Sesión creada correctamente. Se generó la lista de asistencia esperada.');
    }

    public function show(int $id): View
    {
        $sesion = $this->sesionDelColegio($id, ['tipo', 'categoria', 'instructor']);

        $asistencias = $sesion->asistencias()->with(['arbitro.usuario', 'justificacion'])
            ->join('arbitros', 'arbitros.idArbitro', '=', 'asistencias_academicas.idArbitro')
            ->join('usuarios', 'usuarios.idUsuario', '=', 'arbitros.idUsuario')
            ->orderBy('usuarios.nombreUsuario')
            ->select('asistencias_academicas.*')
            ->get();

        return view('academico.sesiones.show', compact('sesion', 'asistencias'));
    }

    public function edit(int $id): View
    {
        $sesion = $this->sesionDelColegio($id, ['tipo']);
        abort_unless($sesion->estadoSesion === SesionAcademica::ESTADO_PROGRAMADA, 403, 'Solo se puede editar una sesión que aún no se ha abierto.');

        $idColegio  = $this->idColegioActivo();
        $tipos      = TipoSesionAcademica::where('idColegio', $idColegio)->where('esActivo', true)->orderBy('orden')->orderBy('etiqueta')->get();

        return view('academico.sesiones.edit', compact('sesion', 'tipos'));
    }

    public function update(UpdateSesionAcademicaRequest $request, int $id): RedirectResponse
    {
        $sesion = $this->sesionDelColegio($id);

        try {
            $this->sesiones->actualizarSesion($sesion, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.sesiones.show', $sesion->idSesion)->with('success', 'Sesión actualizada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $sesion = $this->sesionDelColegio($id);
        $sesion->delete();

        return redirect()->route('academico.sesiones.index')->with('success', 'Sesión eliminada correctamente.');
    }

    public function abrir(int $id): RedirectResponse
    {
        $sesion = $this->sesionDelColegio($id);

        try {
            $this->sesiones->abrirSesion($sesion);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.sesiones.show', $sesion->idSesion)->with('success', 'Sesión abierta. Ya se puede registrar asistencia.');
    }

    /**
     * Cierra la sesión y confirma la lista de asistencia como definitiva
     * (una misma acción — ver SesionAcademicaService::confirmarYCerrarSesion).
     */
    public function cerrar(int $id): RedirectResponse
    {
        $sesion = $this->sesionDelColegio($id);

        try {
            $this->sesiones->confirmarYCerrarSesion($sesion);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.sesiones.show', $sesion->idSesion)->with('success', 'Lista de asistencia confirmada y sesión cerrada.');
    }

    public function cancelar(int $id): RedirectResponse
    {
        $sesion = $this->sesionDelColegio($id);

        try {
            $this->sesiones->cancelarSesion($sesion);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('academico.sesiones.index')->with('success', 'Sesión cancelada.');
    }

    /**
     * Vista del árbitro: próximas sesiones, historial y % de asistencia.
     */
    public function misClases(): View
    {
        $arbitro = $this->arbitroAutenticado(['asistenciasAcademicas']);

        $proximas = SesionAcademica::where('idColegio', $this->idColegioActivo())
            ->whereIn('estadoSesion', [SesionAcademica::ESTADO_PROGRAMADA, SesionAcademica::ESTADO_EN_CURSO])
            ->whereHas('asistencias', fn ($q) => $q->where('idArbitro', $arbitro->idArbitro))
            ->with(['tipo', 'asistencias' => fn ($q) => $q->where('idArbitro', $arbitro->idArbitro)])
            ->orderBy('fechaSesion')
            ->get();

        $historial = SesionAcademica::where('idColegio', $this->idColegioActivo())
            ->where('estadoSesion', SesionAcademica::ESTADO_FINALIZADA)
            ->whereHas('asistencias', fn ($q) => $q->where('idArbitro', $arbitro->idArbitro))
            ->with(['tipo', 'asistencias' => fn ($q) => $q->where('idArbitro', $arbitro->idArbitro)->with('justificacion')])
            ->orderByDesc('fechaSesion')
            ->paginate(15);

        $porcentajeAsistencia = $arbitro->porcentajeAsistencia;

        return view('academico.mis-clases', compact('proximas', 'historial', 'porcentajeAsistencia'));
    }

    private function sesionDelColegio(int $id, array $relaciones = []): SesionAcademica
    {
        $sesion = SesionAcademica::with($relaciones)->findOrFail($id);

        abort_unless((int) $sesion->idColegio === $this->idColegioActivo(), 403);

        return $sesion;
    }
}
