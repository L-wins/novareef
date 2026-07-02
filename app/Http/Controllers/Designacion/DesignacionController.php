<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Exceptions\OptimisticLockException;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\FormatoDesignacion;
use App\Models\Partido;
use App\Models\SedeTorneo;
use App\Models\SlotDesignacion;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use App\Services\SlotDesignacionService;
use App\StateMachines\PartidoStateMachine;
use App\Support\SemanaNavegacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DesignacionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DesignacionService $designaciones,
        private readonly SlotDesignacionService $slots,
    ) {}

    // ── Designador / Ejecutivo ────────────────────────────────────────────────

    /**
     * Lista de partidos del colegio con sus designaciones.
     */
    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        $query = Partido::where('idColegio', $idColegio)
            ->with([
                'torneo',
                'division',
                'sede',
                'formato',
                'designaciones.arbitro.usuario',
                'designaciones.rol',
            ])
            ->orderBy('fechaPartido', 'asc');

        if ($request->filled('torneo')) {
            $query->where('idTorneo', $request->integer('torneo'));
        }

        if ($request->filled('estado')) {
            $query->where('estadoPartido', $request->string('estado'));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fechaPartido', $request->string('fecha'));
        }

        if ($request->filled('division')) {
            $query->where('idDivision', $request->integer('division'));
        }

        $partidos = $query->paginate(20)->withQueryString();

        $torneos = Torneo::where('idColegio', $idColegio)
            ->whereIn('estadoTorneo', ['activo', 'proximo'])
            ->orderByDesc('temporada')
            ->get();

        $criticosCount = Partido::where('idColegio', $idColegio)
            ->where('estadoPartido', 'critico')
            ->count();

        return view('designaciones.index', compact('partidos', 'torneos', 'criticosCount'));
    }

    /**
     * Detalle de un partido — panel de asignación de árbitros.
     */
    public function show(int $id): View
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

        return view('designaciones.show', compact('partido', 'slots', 'posiblesVeedores'));
    }

    /**
     * Formulario crear partido.
     */
    public function crearPartido(): View
    {
        $idColegio = $this->idColegioActivo();

        $torneos = Torneo::where('idColegio', $idColegio)
            ->whereIn('estadoTorneo', ['activo', 'proximo'])
            ->with('divisiones', 'sedes')
            ->orderByDesc('temporada')
            ->get();

        $formatos = FormatoDesignacion::activos()->get();

        return view('designaciones.partido-crear', compact('torneos', 'formatos'));
    }

    /**
     * Guarda un nuevo partido y registra su creación en el historial.
     */
    public function guardarPartido(Request $request): RedirectResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validate([
            'idTorneo'        => 'required|integer',
            'idDivision'      => 'required|integer',
            'idSede'          => 'required|integer',
            'idFormato'       => 'required|integer',
            'equipoLocal'     => 'required|string|max:100',
            'equipoVisitante' => 'required|string|max:100',
            'fechaPartido'    => 'required|date_format:Y-m-d',
            'horaPartido'     => 'required|date_format:H:i',
            'observaciones'   => 'nullable|string|max:1000',
        ]);

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
     * Asigna un árbitro a un rol en el partido con optimistic locking.
     */
    public function asignarArbitro(Request $request, int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validate([
            'idArbitro' => 'required|integer',
            'idRol'     => 'required|integer',
        ]);

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        try {
            $result = $this->designaciones->asignarArbitro(
                $partido,
                (int) $validated['idArbitro'],
                (int) $validated['idRol'],
                $idColegio,
                Auth::id(),
            );

            return response()->json([
                'success'      => true,
                'advertencias' => $result['advertencias'],
                'designacion'  => $result['designacion'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Quita una designación pendiente del partido.
     */
    public function quitarDesignacion(int $designacionId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $designacion = Designacion::where('idDesignacion', $designacionId)
            ->where('idColegio', $idColegio)
            ->firstOrFail();

        try {
            $this->designaciones->quitarDesignacion($designacion, $idColegio, Auth::id());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Cambia el estado del partido usando la state machine.
     */
    public function cambiarEstadoPartido(Request $request, int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validate([
            'estadoNuevo' => 'required|string|in:programado,confirmado,critico,aplazado,en_curso,finalizado,cancelado',
            'detalle'     => 'nullable|string|max:500',
        ]);

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($id);

        // Verificar permisos según estado destino
        $user = Auth::user();
        if (in_array($validated['estadoNuevo'], ['finalizado', 'cancelado'], true)) {
            abort_unless($user->hasRole('ejecutivo') || $user->rolUsuario === 'superadmin', 403);
        } elseif ($validated['estadoNuevo'] === 'aplazado') {
            abort_unless(
                $user->hasRole('ejecutivo') || $user->hasRole('designador') || $user->rolUsuario === 'superadmin',
                403
            );
        }

        try {
            PartidoStateMachine::transicionarCon(
                $partido,
                $validated['estadoNuevo'],
                $user,
                $validated['detalle'] ?? null
            );

            return response()->json(['success' => true]);
        } catch (OptimisticLockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Retorna árbitros disponibles para un partido con todos sus indicadores (AJAX).
     */
    public function getArbitrosDisponibles(int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        return response()->json($this->designaciones->candidatosParaPartido($partido, $idColegio));
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
     * Asigna (o cambia) el veedor de un partido.
     */
    public function asignarVeedor(Request $request, int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validate([
            'idVeedor' => 'nullable|integer|exists:usuarios,idUsuario',
        ]);

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        $idVeedor = isset($validated['idVeedor']) ? (int) $validated['idVeedor'] : null;

        try {
            $this->designaciones->asignarVeedor($partido, $idVeedor, $idColegio, Auth::id());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        return response()->json(['success' => true]);
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

    // ── Árbitro ─────

    /**
     * Vista detalle de un partido para el árbitro (requiere designación confirmada).
     */
    public function detallePartido(int $id): View
    {
        $arbitro = $this->arbitroAutenticado();

        $designacion = Designacion::where('idArbitro', $arbitro->idArbitro)
            ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->whereHas('partido', fn ($q) => $q->where('idPartido', $id)
                ->where('estadoPartido', '!=', Partido::ESTADO_BORRADOR))
            ->with([
                'partido.torneo',
                'partido.division',
                'partido.sede',
                'partido.formato',
                'partido.veedor',
                'partido.designaciones.arbitro.usuario',
                'partido.designaciones.arbitro.categoria',
                'partido.designaciones.rol',
                'partido.designaciones.calificacion',
                'rol',
                'calificacion',
            ])
            ->firstOrFail();

        $partido = $designacion->partido;

        // Slots con árbitro asignado — fuente de verdad del equipo arbitral
        $slots = SlotDesignacion::where('idPartido', $partido->idPartido)
            ->with(['rol', 'designacion.arbitro.usuario', 'designacion.arbitro.categoria'])
            ->orderBy('idRol')
            ->orderBy('numeroSlot')
            ->get()
            ->sortBy(fn (SlotDesignacion $s) => [$s->rol?->orden ?? 99, $s->numeroSlot])
            ->values();

        $pago      = $this->designaciones->calcularPago($designacion);
        $esCentral = $designacion->rol?->nombre === 'Central';

        return view('mis-partidos.detalle', compact('designacion', 'partido', 'arbitro', 'slots', 'pago', 'esCentral'));
    }

    /**
     * Vista de mis partidos para el árbitro autenticado.
     * Los partidos en borrador no son visibles para los árbitros.
     */
    public function misPartidos(): View
    {
        $arbitro = $this->arbitroAutenticado();
        $hoy     = now()->startOfDay();
        $manana  = now()->addDay()->startOfDay();

        $base = Designacion::where('idArbitro', $arbitro->idArbitro)
            ->whereHas('partido', fn ($q) => $q->where('estadoPartido', '!=', Partido::ESTADO_BORRADOR))
            ->with(['partido.torneo', 'partido.sede', 'partido.division', 'partido.formato', 'partido.designaciones.rol', 'rol'])
            ->get()
            ->each(fn (Designacion $d) => $d->setAttribute('pago', $this->designaciones->calcularPago($d)));

        $hoyPartidos    = $base->filter(fn ($d) => $d->partido->fechaPartido->isToday());
        $mananaPartidos = $base->filter(fn ($d) => $d->partido->fechaPartido->isTomorrow());
        $proximos       = $base->filter(fn ($d) => $d->partido->fechaPartido->gt($manana->copy()->endOfDay()));
        $historial      = $base->filter(fn ($d) => $d->partido->fechaPartido->lt($hoy))
                               ->sortByDesc(fn ($d) => $d->partido->fechaPartido);

        $pendientesCount = $base->filter(fn ($d) => $d->estaPendiente() && ! $d->partido->fechaPartido->isPast())->count();

        return view('mis-partidos.index', compact('hoyPartidos', 'mananaPartidos', 'proximos', 'historial', 'pendientesCount', 'arbitro'));
    }

    /**
     * El árbitro Central finaliza el partido en curso.
     */
    public function finalizarPartido(int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();
        $arbitro   = $this->arbitroAutenticado();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($id);

        $esCentral = Designacion::where('idPartido', $partido->idPartido)
            ->where('idArbitro', $arbitro->idArbitro)
            ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Central'))
            ->exists();

        abort_unless($esCentral, 403, 'Solo el árbitro Central puede finalizar el partido.');

        if ($partido->estadoPartido !== Partido::ESTADO_EN_CURSO) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede finalizar un partido en curso.',
            ], 422);
        }

        try {
            // La state machine despacha NotificarFinalizacionJob en sus efectos
            PartidoStateMachine::transicionarCon(
                $partido,
                Partido::ESTADO_FINALIZADO,
                Auth::user(),
                'Finalizado por el árbitro Central'
            );

            return response()->json(['success' => true]);
        } catch (OptimisticLockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * El árbitro confirma su designación.
     */
    public function confirmarDesignacion(int $id): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        $designacion = Designacion::where('idDesignacion', $id)
            ->where('idArbitro', $arbitro->idArbitro)
            ->firstOrFail();

        try {
            $partidoCompleto = $this->designaciones->confirmarDesignacion($designacion, $arbitro, Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'         => true,
            'partidoCompleto' => $partidoCompleto,
        ]);
    }

    /**
     * El árbitro rechaza su designación con un motivo.
     */
    public function rechazarDesignacion(Request $request, int $id): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        $validated = $request->validate([
            'motivo' => 'required|string|min:10|max:300',
        ]);

        $designacion = Designacion::where('idDesignacion', $id)
            ->where('idArbitro', $arbitro->idArbitro)
            ->firstOrFail();

        try {
            $this->designaciones->rechazarDesignacion($designacion, $arbitro, $validated['motivo'], Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Vista de disponibilidad semanal (designador/ejecutivo).
     */
    public function disponibilidadGeneral(Request $request): View
    {
        $semana    = SemanaNavegacion::desde($request->query('semana'));
        $idColegio = $this->idColegioActivo();

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->with([
                'usuario',
                'disponibilidades' => fn ($q) => $q->whereBetween(
                    'fechaDisponibilidad',
                    [$semana->lunes->toDateString(), $semana->domingo->toDateString()]
                ),
                'indisponibilidadesExtraordinarias' => fn ($q) => $q->whereBetween(
                    'fechaAfectada',
                    [$semana->lunes->toDateString(), $semana->domingo->toDateString()]
                ),
            ])
            ->orderBy('idArbitro')
            ->get();

        return view('disponibilidad.general', [
            'arbitros' => $arbitros,
            'semana'   => $semana,
            'franjas'  => DisponibilidadArbitro::getFranjas(),
        ]);
    }
}
