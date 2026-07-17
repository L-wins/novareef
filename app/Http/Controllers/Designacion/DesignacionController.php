<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Exceptions\OptimisticLockException;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\ActualizarPartidoRequest;
use App\Http\Requests\Designacion\GuardarPartidoRequest;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\SlotDesignacion;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use App\Services\ReporteDesignacionesService;
use App\Services\SlotDesignacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CRUD del partido en sí (vista designador/ejecutivo): listar, ver, crear,
 * editar, publicar, eliminar, generar acta. Las acciones sobre designaciones
 * (asignar/reasignar/estado/veedor) viven en DesignacionAccionesController,
 * y todo lo que ve/hace el árbitro en MisPartidosController — separados de
 * aquí porque el archivo original superaba las ~700 líneas documentadas
 * (ver auditoría de plataforma, punto 3.1).
 */
class DesignacionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DesignacionService $designaciones,
        private readonly SlotDesignacionService $slots,
        private readonly ReporteDesignacionesService $reportes,
    ) {}

    /**
     * Sin ?torneo=: grid de torneos con conteo de partidos.
     * Con ?torneo=X: lista de partidos de ese torneo (con filtros de estado/fecha/división).
     */
    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        if (! $request->filled('torneo')) {
            $torneos       = $this->reportes->gridTorneosConConteos($idColegio);
            $criticosCount = $this->reportes->criticosCount($idColegio);

            return view('designaciones.index', compact('torneos', 'criticosCount'));
        }

        $torneo = Torneo::where('idColegio', $idColegio)
            ->findOrFail($request->integer('torneo'));

        $partidos = $this->reportes->listadoPartidosDeTorneo($idColegio, $torneo, [
            'estado'   => $request->string('estado')->toString() ?: null,
            'fecha'    => $request->string('fecha')->toString() ?: null,
            'division' => $request->filled('division') ? $request->integer('division') : null,
        ]);

        $criticosCount = $this->reportes->criticosCount($idColegio, $torneo->idTorneo);

        return view('designaciones.partidos-torneo', compact('partidos', 'torneo', 'criticosCount'));
    }

    /**
     * Detalle de un partido — panel de asignación de árbitros.
     */
    public function show(int $id): View
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)
            ->with([
                'torneo.divisiones',
                'torneo.sedes',
                'division',
                'sede',
                'formato',
                'designaciones.arbitro.usuario',
                'designaciones.arbitro.categoria',
                'designaciones.rol',
                'historial.usuarioAccion',
                'historial.arbitro.usuario',
            ])
            ->findOrFail($id);

        // Partidos anteriores al sistema de slots se les crean al vuelo
        $this->slots->asegurar($partido);

        $slots = SlotDesignacion::where('idPartido', $partido->idPartido)
            ->with(['rol', 'designacion.arbitro.usuario', 'designacion.arbitro.categoria'])
            ->orderBy('idRol')
            ->orderBy('numeroSlot')
            ->get()
            ->sortBy(fn (SlotDesignacion $s) => [$s->rol?->orden ?? 99, $s->numeroSlot])
            ->values();

        $posiblesVeedores = User::where('idColegio', $idColegio)
            ->whereIn('rolUsuario', ['ejecutivo', 'tesorero', 'designador', 'sanciones', 'tecnico', 'veedor'])
            ->orderBy('nombreUsuario')
            ->get();

        $formatos = FormatoDesignacion::activos()->get();

        return view('designaciones.show', compact('partido', 'slots', 'posiblesVeedores', 'formatos'));
    }

    /**
     * Formulario crear partido.
     */
    public function crearPartido(): View
    {
        $idColegio = $this->idColegioActivo();

        // limit: mismo criterio que el dropdown de torneos ya corregido en
        // Finanzas — es un <select>, no un listado paginado, pero tampoco
        // debe traer sin tope todos los torneos activos/próximos del colegio.
        $torneos = Torneo::where('idColegio', $idColegio)
            ->whereIn('estadoTorneo', ['activo', 'proximo'])
            ->with('divisiones', 'sedes')
            ->orderByDesc('temporada')
            ->limit(100)
            ->get();

        $formatos = FormatoDesignacion::activos()->get();

        return view('designaciones.partido-crear', compact('torneos', 'formatos'));
    }

    /**
     * Guarda un nuevo partido y registra su creación en el historial.
     */
    public function guardarPartido(GuardarPartidoRequest $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

        // Verificar pertenencia al colegio
        $torneo = Torneo::where('idTorneo', $validated['idTorneo'])
            ->where('idColegio', $idColegio)
            ->firstOrFail();

        abort_unless(
            $torneo->divisiones()->where('idDivision', $validated['idDivision'])->exists(),
            403,
            'La división no pertenece a este torneo.'
        );

        abort_unless(
            SedeTorneo::where('idSede', $validated['idSede'])
                ->where('idTorneo', $torneo->idTorneo)
                ->exists(),
            403,
            'La sede no pertenece a este torneo.'
        );

        $partido = $this->designaciones->crearPartido($idColegio, $validated, Auth::id());

        return redirect()
            ->route('designaciones.show', $partido->idPartido)
            ->with('success', 'Partido creado en borrador. Asigna los árbitros y publícalo.');
    }

    /**
     * Edita fecha/hora/sede/equipos de un partido — solo mientras sigue en
     * borrador (una vez publicado, esos datos ya se notificaron al árbitro).
     */
    public function actualizarPartido(ActualizarPartidoRequest $request, int $id): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($id);

        if ($partido->estadoPartido !== Partido::ESTADO_BORRADOR) {
            return back()->with('error', 'Solo se puede editar un partido mientras está en borrador.');
        }

        $validated = $request->validated();

        abort_unless(
            Torneo::where('idTorneo', $partido->idTorneo)
                ->whereHas('divisiones', fn ($q) => $q->where('idDivision', $validated['idDivision']))
                ->exists(),
            403,
            'La división no pertenece a este torneo.'
        );

        if (! empty($validated['idSede'])) {
            abort_unless(
                SedeTorneo::where('idSede', $validated['idSede'])
                    ->where('idTorneo', $partido->idTorneo)
                    ->exists(),
                403,
                'La sede no pertenece a este torneo.'
            );
        }

        $partido->update($validated);

        return back()->with('success', 'Partido actualizado correctamente.');
    }

    /**
     * Publica un partido en borrador: pasa a programado y notifica a los árbitros.
     */
    public function publicarPartido(int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->with('formato')->findOrFail($id);

        try {
            $this->designaciones->publicarPartido($partido, Auth::user());

            return response()->json([
                'success' => true,
                'mensaje' => 'Partido publicado. Los árbitros han sido notificados.',
            ]);
        } catch (OptimisticLockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Elimina un partido en borrador junto con designaciones/slots/historial.
     */
    public function eliminarPartido(int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($id);

        try {
            $this->designaciones->eliminarPartido($partido, $idColegio);

            return response()->json([
                'success' => true,
                'mensaje' => 'Partido eliminado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Carga las divisiones de un torneo (AJAX).
     */
    public function getDivisiones(int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $divisiones = Torneo::where('idTorneo', $id)
            ->where('idColegio', $idColegio)
            ->firstOrFail()
            ->divisiones()
            ->select('idDivision', 'nombreDivision')
            ->get();

        return response()->json($divisiones);
    }

    /**
     * Carga las sedes de un torneo (AJAX).
     */
    public function getSedes(int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $sedes = SedeTorneo::where('idTorneo', $id)
            ->whereHas('torneo', fn ($q) => $q->where('idColegio', $idColegio))
            ->select('idSede', 'nombreSede', 'municipio')
            ->get();

        return response()->json($sedes);
    }

    /**
     * Genera el PDF del acta de designación del partido.
     */
    public function generarActa(int $partidoId): mixed
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)
            ->with([
                'torneo',
                'division',
                'sede',
                'formato',
                'designaciones.arbitro.usuario',
                'designaciones.arbitro.categoria',
                'designaciones.rol',
                'veedor',
                'colegio',
            ])
            ->findOrFail($partidoId);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.acta-designacion', ['partido' => $partido, 'generadoPor' => Auth::user()]);
        $pdf->setPaper('a4', 'portrait');

        $nombre = "acta-{$partido->equipoLocal}-vs-{$partido->equipoVisitante}-{$partido->fechaPartido->format('Y-m-d')}.pdf";

        return $pdf->download($nombre);
    }

    /**
     * PDF con todos los partidos del torneo en el mismo formato visual del
     * Word que envían las asociaciones (ver importador, M04), ya con los
     * árbitros designados. Útil para reenviarle a la asociación el mismo
     * documento con los nombres puestos.
     */
    public function generarListado(Request $request, int $idTorneo): mixed
    {
        $idColegio = $this->idColegioActivo();

        $torneo = Torneo::where('idTorneo', $idTorneo)
            ->where('idColegio', $idColegio)
            ->firstOrFail();

        $query = Partido::where('idColegio', $idColegio)
            ->where('idTorneo', $idTorneo)
            ->with(['division', 'sede', 'slots.rol', 'slots.designacion.arbitro.usuario'])
            ->orderBy('fechaPartido')
            ->orderBy('horaPartido');

        if ($request->filled('division')) {
            $query->where('idDivision', $request->integer('division'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fechaPartido', '>=', $request->string('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fechaPartido', '<=', $request->string('hasta'));
        }

        $partidos = $query->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.listado-partidos', ['partidos' => $partidos, 'torneo' => $torneo, 'generadoPor' => Auth::user()]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("listado-partidos-{$torneo->nombreTorneo}-" . now()->format('Y-m-d') . '.pdf');
    }
}
