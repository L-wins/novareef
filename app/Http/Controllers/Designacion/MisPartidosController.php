<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Exceptions\OptimisticLockException;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\RechazarDesignacionRequest;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\Partido;
use App\Models\RolPartido;
use App\Models\SlotDesignacion;
use App\Models\Torneo;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Todo lo que ve/hace el árbitro sobre sus propias designaciones: mis
 * partidos, historial, confirmar/rechazar, finalizar. Separado de
 * DesignacionController (que se quedó con el CRUD del partido, vista
 * designador/ejecutivo) porque ese archivo superaba las ~700 líneas
 * documentadas — ver auditoría de plataforma, punto 3.1.
 */
class MisPartidosController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DesignacionService $designaciones,
    ) {}

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

        $pendientesCount = $base->filter(fn ($d) => $d->estaPendiente() && ! $d->partido->fechaPartido->isPast())->count();

        return view('mis-partidos.index', compact('hoyPartidos', 'mananaPartidos', 'proximos', 'pendientesCount', 'arbitro'));
    }

    /**
     * Historial paginado de designaciones pasadas del árbitro autenticado,
     * con filtros (torneo, rol, estado, rango de fechas) y estadísticas de carrera.
     */
    public function historialPartidos(Request $request): View
    {
        $arbitro = $this->arbitroAutenticado();

        $historial = $this->queryHistorial($arbitro, $request)
            ->with(['partido.torneo', 'partido.sede', 'partido.division', 'partido.formato', 'rol'])
            ->paginate(20)
            ->withQueryString();

        $historial->getCollection()
            ->each(fn (Designacion $d) => $d->setAttribute('pago', $this->designaciones->calcularPago($d)));

        // Opciones de filtros: solo torneos donde el árbitro tuvo designaciones
        $torneos = Torneo::whereIn('idTorneo', function ($q) use ($arbitro) {
            $q->select('partidos.idTorneo')
                ->from('designaciones')
                ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
                ->where('designaciones.idArbitro', $arbitro->idArbitro);
        })->orderByDesc('temporada')->get();

        $roles = RolPartido::activos()->get();

        return view('mis-partidos.historial', [
            'historial' => $historial,
            'arbitro'   => $arbitro,
            'stats'     => $this->statsHistorial($arbitro),
            'torneos'   => $torneos,
            'roles'     => $roles,
        ]);
    }

    /**
     * Exporta el historial (con los filtros activos) a PDF.
     */
    public function historialPdf(Request $request): mixed
    {
        $arbitro = $this->arbitroAutenticado();

        $historial = $this->queryHistorial($arbitro, $request)
            ->with(['partido.torneo', 'partido.sede', 'rol'])
            ->get()
            ->each(fn (Designacion $d) => $d->setAttribute('pago', $this->designaciones->calcularPago($d)));

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.historial-arbitro', [
            'historial' => $historial,
            'arbitro'   => $arbitro,
            'stats'     => $this->statsHistorial($arbitro),
        ]);
        $pdf->setPaper('a4', 'portrait');

        $nombre = 'historial-partidos-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($nombre);
    }

    /**
     * Query base del historial: designaciones pasadas del árbitro con filtros de request.
     * Join con partidos para poder ordenar y filtrar por columnas del partido.
     */
    private function queryHistorial(Arbitro $arbitro, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Designacion::query()
            ->select('designaciones.*')
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->where('designaciones.idArbitro', $arbitro->idArbitro)
            ->where('partidos.estadoPartido', '!=', Partido::ESTADO_BORRADOR)
            ->whereDate('partidos.fechaPartido', '<', now()->toDateString())
            ->orderByDesc('partidos.fechaPartido')
            ->orderByDesc('partidos.horaPartido');

        if ($request->filled('torneo')) {
            $query->where('partidos.idTorneo', $request->integer('torneo'));
        }

        if ($request->filled('rol')) {
            $query->where('designaciones.idRol', $request->integer('rol'));
        }

        if ($request->filled('estado')) {
            $query->where('designaciones.estadoDesignacion', $request->string('estado'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('partidos.fechaPartido', '>=', $request->string('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('partidos.fechaPartido', '<=', $request->string('hasta'));
        }

        return $query;
    }

    /**
     * Estadísticas de carrera del árbitro (independientes de los filtros de la tabla).
     * "Dirigido" = designación confirmada en partido pasado que sí se jugó
     * (se excluyen borrador, cancelado y aplazado).
     */
    private function statsHistorial(Arbitro $arbitro): array
    {
        $dirigidos = Designacion::query()
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->join('roles_partido', 'roles_partido.idRol', '=', 'designaciones.idRol')
            ->where('designaciones.idArbitro', $arbitro->idArbitro)
            ->where('designaciones.estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->whereNotIn('partidos.estadoPartido', [Partido::ESTADO_BORRADOR, Partido::ESTADO_CANCELADO, Partido::ESTADO_APLAZADO])
            ->whereDate('partidos.fechaPartido', '<', now()->toDateString());

        $porRol = (clone $dirigidos)
            ->groupBy('roles_partido.nombre', 'roles_partido.orden')
            ->orderBy('roles_partido.orden')
            ->selectRaw('roles_partido.nombre as rol, COUNT(*) as total')
            ->pluck('total', 'rol');

        $torneosDistintos = (clone $dirigidos)
            ->distinct('partidos.idTorneo')
            ->count('partidos.idTorneo');

        $rechazadas = Designacion::query()
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->where('designaciones.idArbitro', $arbitro->idArbitro)
            ->where('designaciones.estadoDesignacion', Designacion::ESTADO_RECHAZADA)
            ->whereDate('partidos.fechaPartido', '<', now()->toDateString())
            ->count();

        return [
            'totalDirigidos' => $porRol->sum(),
            'porRol'         => $porRol,
            'torneos'        => $torneosDistintos,
            'rechazadas'     => $rechazadas,
        ];
    }

    /**
     * El árbitro Central finaliza un partido confirmado. No existe estado
     * "en curso": el partido queda en 'confirmado' hasta que el Central o el
     * designador lo finalizan manualmente.
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

        if ($partido->estadoPartido !== Partido::ESTADO_CONFIRMADO) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede finalizar un partido confirmado.',
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
     * Fallback GET para los enlaces "Confirmar" de correos antiguos, que
     * apuntaban directo al endpoint POST: lleva al árbitro a la card de su
     * designación en Mis partidos, donde confirma con el botón real.
     */
    public function redirigirConfirmacionEmail(int $id): RedirectResponse
    {
        return redirect()
            ->route('mis-partidos.index')
            ->withFragment("desig-card-{$id}");
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
    public function rechazarDesignacion(RechazarDesignacionRequest $request, int $id): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        $validated = $request->validated();

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
}
