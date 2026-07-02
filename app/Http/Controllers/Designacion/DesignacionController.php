<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Events\DesignacionActualizadaEvent;
use App\Events\PartidoActualizadoEvent;
use App\Exceptions\OptimisticLockException;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Jobs\NotificarRechazoJob;
use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\FormatoDesignacion;
use App\Models\HistorialDesignacion;
use App\Models\IndisponibilidadExtraordinaria;
use App\Models\Partido;
use App\Models\CalificacionArbitro;
use App\Models\RolPartido;
use App\Models\SedeTorneo;
use App\Models\SlotDesignacion;
use App\Models\TarifaTorneo;
use App\Models\Torneo;
use App\Models\User;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use App\Support\SemanaNavegacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DesignacionController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DesignacionService $designaciones,
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
        $this->asegurarSlots($partido);

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

        $formatos = FormatoDesignacion::where('esActivo', true)
            ->orderBy('orden')
            ->get();

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

        $partido = DB::transaction(function () use ($validated, $idColegio): Partido {
            $partido = Partido::create([
                'idColegio'       => $idColegio,
                'idTorneo'        => $validated['idTorneo'],
                'idDivision'      => $validated['idDivision'],
                'idSede'          => $validated['idSede'],
                'idFormato'       => $validated['idFormato'],
                'equipoLocal'     => $validated['equipoLocal'],
                'equipoVisitante' => $validated['equipoVisitante'],
                'fechaPartido'    => $validated['fechaPartido'],
                'horaPartido'     => $validated['horaPartido'],
                'estadoPartido'   => Partido::ESTADO_BORRADOR,
                'version'         => 0,
                'observaciones'   => $validated['observaciones'] ?? null,
            ]);

            // Slots de roles según formato — fuente de verdad para la asignación
            $this->crearSlotsPartido($partido->load('formato'));

            HistorialDesignacion::create([
                'idPartido'       => $partido->idPartido,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => Auth::id(),
                'tipoAccion'      => HistorialDesignacion::TIPO_PARTIDO_CREADO,
                'estadoNuevo'     => Partido::ESTADO_BORRADOR,
                'detalle'         => "Partido creado: {$partido->equipoLocal} vs {$partido->equipoVisitante}",
            ]);

            return $partido;
        });

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

        $partido = Partido::where('idColegio', $idColegio)
            ->with('formato')
            ->findOrFail($id);

        if ($partido->estadoPartido !== Partido::ESTADO_BORRADOR) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden publicar partidos en borrador.',
            ], 422);
        }

        $tieneCentral = $partido->designaciones()
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'Central'))
            ->exists();

        if (! $tieneCentral) {
            return response()->json([
                'success' => false,
                'message' => 'Debes asignar al menos el árbitro Central antes de publicar.',
            ], 422);
        }

        try {
            // La state machine despacha NotificarPublicacionJob en sus efectos
            PartidoStateMachine::transicionarCon(
                $partido,
                Partido::ESTADO_PROGRAMADO,
                Auth::user(),
                'Partido publicado'
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Partido publicado. Los árbitros han sido notificados.',
            ]);
        } catch (OptimisticLockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
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

        try {
            $result = DB::transaction(function () use ($validated, $partidoId, $idColegio): array {
                $partido = Partido::lockForUpdate()->where('idColegio', $idColegio)->findOrFail($partidoId);

                // Después de publicar solo se puede cambiar el veedor
                abort_if(
                    $partido->estadoPartido !== Partido::ESTADO_BORRADOR,
                    422,
                    'No puedes asignar árbitros después de publicar el partido. Solo puedes asignar el veedor.'
                );

                $arbitro = Arbitro::where('idArbitro', $validated['idArbitro'])
                    ->where('idColegio', $idColegio)
                    ->with('usuario', 'disponibilidades', 'indisponibilidadesExtraordinarias')
                    ->firstOrFail();

                // No duplicar árbitro en el mismo partido
                abort_if(
                    $partido->designaciones()->where('idArbitro', $arbitro->idArbitro)->exists(),
                    422,
                    'Este árbitro ya está designado en este partido.'
                );

                // Slots son la fuente de verdad: tomar el primer slot libre del rol
                $this->asegurarSlots($partido->load('formato'));

                $slot = SlotDesignacion::where('idPartido', $partido->idPartido)
                    ->where('idRol', $validated['idRol'])
                    ->whereNull('idDesignacion')
                    ->orderBy('numeroSlot')
                    ->lockForUpdate()
                    ->first();

                abort_if($slot === null, 422, 'No hay slots disponibles para este rol.');

                $advertencias = $this->designaciones->calcularAdvertencias($arbitro, $partido);

                $designacion = Designacion::create([
                    'idPartido'          => $partido->idPartido,
                    'idArbitro'          => $arbitro->idArbitro,
                    'idRol'              => $validated['idRol'],
                    'idColegio'          => $idColegio,
                    'estadoDesignacion'  => Designacion::ESTADO_PENDIENTE,
                    'idUsuarioDesignador'=> Auth::id(),
                ]);

                $slot->update(['idDesignacion' => $designacion->idDesignacion]);

                HistorialDesignacion::create([
                    'idDesignacion'   => $designacion->idDesignacion,
                    'idPartido'       => $partido->idPartido,
                    'idArbitro'       => $arbitro->idArbitro,
                    'idColegio'       => $idColegio,
                    'idUsuarioAccion' => Auth::id(),
                    'tipoAccion'      => HistorialDesignacion::TIPO_ASIGNADO,
                    'detalle'         => implode(', ', array_filter([
                        $advertencias['sinDisponibilidad']   ? 'Sin disponibilidad reportada' : '',
                        $advertencias['tieneExtraordinaria'] ? 'Indisponibilidad extraordinaria' : '',
                        $advertencias['esSuspendido']        ? 'Árbitro suspendido' : '',
                        $advertencias['advertenciaTiempo']   ? "Partido cercano ({$advertencias['minutosAlPartidoCercano']} min)" : '',
                    ])) ?: null,
                ]);

                // El árbitro se notifica al publicar el partido (NotificarPublicacionJob),
                // nunca mientras el partido siga en borrador.
                broadcast(new DesignacionActualizadaEvent($designacion))->toOthers();

                return [
                    'designacion' => $designacion->load(['arbitro.usuario', 'rol']),
                    'advertencias'=> $advertencias,
                ];
            });

            return response()->json([
                'success'      => true,
                'advertencias' => $result['advertencias'],
                'designacion'  => $result['designacion'],
            ]);
        } catch (\Throwable $e) {
            Log::error('asignarArbitro error', ['error' => $e->getMessage()]);

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

        if ($designacion->estaConfirmada()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede quitar una designación ya confirmada.',
            ], 422);
        }

        DB::transaction(function () use ($designacion, $idColegio): void {
            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $designacion->idArbitro,
                'idColegio'       => $idColegio,
                'idUsuarioAccion' => Auth::id(),
                'tipoAccion'      => HistorialDesignacion::TIPO_QUITADO,
            ]);

            $designacion->delete();

            $partido = Partido::find($designacion->idPartido);
            if ($partido) {
                broadcast(new PartidoActualizadoEvent($partido))->toOthers();
            }
        });

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
    public function getArbitrosDisponibles(Request $request, int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        $fecha     = $partido->fechaPartido;
        $franjaNeed= $this->franjaDesdeHora($partido->horaPartido ?? '00:00');

        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->whereNotIn('estadoArbitro', ['retirado'])
            ->with([
                'usuario',
                'categoria',
                'disponibilidades' => fn ($q) => $q->where('fechaDisponibilidad', $fecha),
                'indisponibilidadesExtraordinarias' => fn ($q) => $q->where('fechaAfectada', $fecha),
            ])
            ->get();

        // IDs ya asignados en este partido
        $yaAsignados = $partido->designaciones()->pluck('idArbitro')->flip();

        // Choques de horario para todos los árbitros con una sola query
        $advertenciasPorArbitro = $this->designaciones->advertenciasPorLista($arbitros, $partido);

        $resultado = $arbitros->map(function (Arbitro $a) use ($franjaNeed, $yaAsignados, $advertenciasPorArbitro): array {
            $disponibilidad = $a->disponibilidades->first();
            $extraordinaria = $a->indisponibilidadesExtraordinarias->first();

            $dispEstado = 'sin_reporte';
            $franjaDisp = null;
            $franjaLabel= null;

            if ($extraordinaria) {
                $dispEstado = 'extraordinaria';
            } elseif ($disponibilidad) {
                $dispEstado = $this->franjaCoincide($disponibilidad->franjaHoraria, $franjaNeed)
                    ? 'disponible'
                    : 'sin_reporte';
                $franjaDisp  = $disponibilidad->franjaHoraria;
                $franjaLabel = DisponibilidadArbitro::getFranjas()[$franjaDisp] ?? $franjaDisp;
            }

            $advertencias = $advertenciasPorArbitro[$a->idArbitro];

            return [
                'idArbitro'              => $a->idArbitro,
                'nombreUsuario'          => $a->usuario?->nombreUsuario,
                'codigoCarnet'           => $a->codigoCarnet,
                'nombreCategoria'        => $a->categoria?->nombreCategoria,
                'disponibilidad'         => $dispEstado,
                'franjaDisponible'       => $franjaDisp,
                'franjaLabel'            => $franjaLabel,
                'advertenciaTiempo'      => $advertencias['advertenciaTiempo'],
                'minutosAlPartidoCercano'=> $advertencias['minutosAlPartidoCercano'],
                'yaAsignado'             => isset($yaAsignados[$a->idArbitro]),
                'esSuspendido'           => $a->estadoArbitro === 'suspendido',
                'estadoArbitro'          => $a->estadoArbitro,
            ];
        });

        // Ordenar: disponibles sin advertencias → disponibles con advertencias
        //          → sin reporte → suspendidos al final
        $ordenada = $resultado->sortBy(fn ($r) => match (true) {
            $r['yaAsignado']                                    => 99,
            $r['esSuspendido']                                  => 4,
            $r['disponibilidad'] === 'extraordinaria'           => 3,
            $r['disponibilidad'] === 'sin_reporte'              => 2,
            $r['advertenciaTiempo']                             => 1,
            default                                             => 0,
        })->values();

        return response()->json($ordenada);
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

        // Si se proporcionó un veedor, verificar que pertenece al mismo colegio
        if (! empty($validated['idVeedor'])) {
            abort_unless(
                User::where('idUsuario', $validated['idVeedor'])
                    ->where('idColegio', $idColegio)
                    ->exists(),
                403,
                'El veedor no pertenece a este colegio.'
            );
        }

        $partido->update(['idVeedor' => $validated['idVeedor'] ?? null]);

        HistorialDesignacion::create([
            'idPartido'       => $partido->idPartido,
            'idColegio'       => $idColegio,
            'idUsuarioAccion' => Auth::id(),
            'tipoAccion'      => HistorialDesignacion::TIPO_ESTADO_PARTIDO_CAMBIADO,
            'detalle'         => $validated['idVeedor']
                ? 'Veedor asignado: ' . User::find($validated['idVeedor'])?->nombreUsuario
                : 'Veedor removido',
        ]);

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

    // ── Árbitro ───────────────────────────────────────────────────────────────

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

        $pago      = $this->calcularPago($designacion);
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
            ->each(fn (Designacion $d) => $d->setAttribute('pago', $this->calcularPago($d)));

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

        if (! $designacion->estaPendiente()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta designación no está en estado pendiente.',
            ], 422);
        }

        $partidoCompleto = DB::transaction(function () use ($designacion, $arbitro): bool {
            $designacion->update([
                'estadoDesignacion' => Designacion::ESTADO_CONFIRMADA,
                'fechaConfirmacion' => now(),
            ]);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $arbitro->idArbitro,
                'idColegio'       => $designacion->idColegio,
                'idUsuarioAccion' => Auth::id(),
                'tipoAccion'      => HistorialDesignacion::TIPO_CONFIRMADO,
                'estadoAnterior'  => Designacion::ESTADO_PENDIENTE,
                'estadoNuevo'     => Designacion::ESTADO_CONFIRMADA,
            ]);

            broadcast(new DesignacionActualizadaEvent($designacion->fresh()))->toOthers();

            // Verificar si todas las designaciones están confirmadas
            $partido = Partido::with('formato')->find($designacion->idPartido);
            if ($partido && $partido->estaCompleto()) {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_CONFIRMADO,
                    Auth::user()
                );
                return true;
            }

            return false;
        });

        return response()->json([
            'success'        => true,
            'partidoCompleto'=> $partidoCompleto,
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

        if (! $designacion->estaPendiente()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden rechazar designaciones pendientes.',
            ], 422);
        }

        DB::transaction(function () use ($designacion, $validated, $arbitro): void {
            $designacion->update([
                'estadoDesignacion' => Designacion::ESTADO_RECHAZADA,
                'motivoRechazo'     => $validated['motivo'],
                'fechaRechazo'      => now(),
            ]);

            HistorialDesignacion::create([
                'idDesignacion'   => $designacion->idDesignacion,
                'idPartido'       => $designacion->idPartido,
                'idArbitro'       => $arbitro->idArbitro,
                'idColegio'       => $designacion->idColegio,
                'idUsuarioAccion' => Auth::id(),
                'tipoAccion'      => HistorialDesignacion::TIPO_RECHAZADO,
                'estadoAnterior'  => Designacion::ESTADO_PENDIENTE,
                'estadoNuevo'     => Designacion::ESTADO_RECHAZADA,
                'detalle'         => $validated['motivo'],
            ]);

            // Si el partido estaba confirmado → regresa a programado
            $partido = Partido::find($designacion->idPartido);
            if ($partido && $partido->estadoPartido === Partido::ESTADO_CONFIRMADO) {
                PartidoStateMachine::transicionarCon(
                    $partido,
                    Partido::ESTADO_PROGRAMADO,
                    Auth::user(),
                    "Árbitro rechazó designación: {$validated['motivo']}"
                );
            }

            NotificarRechazoJob::dispatch($designacion->load(['partido', 'arbitro.usuario', 'designador']));
            broadcast(new PartidoActualizadoEvent($partido ?? Partido::find($designacion->idPartido)))->toOthers();
        });

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

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function franjaDesdeHora(string $hora): string
    {
        $h = (int) substr($hora, 0, 2);

        return match (true) {
            $h >= 6  && $h < 12 => 'am',
            $h >= 12 && $h < 18 => 'pm',
            default             => 'noche',
        };
    }

    private function franjaCoincide(string $franjaArbitro, string $franjaPartido): bool
    {
        $mapa = [
            'am'        => ['am'],
            'pm'        => ['pm'],
            'noche'     => ['noche'],
            'am_pm'     => ['am', 'pm'],
            'am_noche'  => ['am', 'noche'],
            'pm_noche'  => ['pm', 'noche'],
            'todo_el_dia'=> ['am', 'pm', 'noche'],
        ];

        return in_array($franjaPartido, $mapa[$franjaArbitro] ?? [], true);
    }

    /**
     * Define cuántos slots de cada rol exige el formato del partido.
     * VAR y AVAR nunca en formatos estándar.
     */
    private function definicionSlots(?object $formato): array
    {
        $nombreFormato = strtolower($formato?->nombre ?? '');

        return match (true) {
            str_contains($nombreFormato, 'solo')   => ['Central' => 1],
            str_contains($nombreFormato, 'dupla')  => ['Central' => 1, 'Asistente' => 1],
            str_contains($nombreFormato, 'cuarto') => ['Central' => 1, 'Asistente' => 2, 'Cuarto' => 1],
            str_contains($nombreFormato, 'terna')  => ['Central' => 1, 'Asistente' => 2],
            default                                => ['Central' => 1, 'Asistente' => 1],
        };
    }

    /**
     * Crea los slots de designación del partido según su formato.
     * Idempotente: usa firstOrCreate sobre la clave única (partido, rol, numeroSlot).
     */
    private function crearSlotsPartido(Partido $partido): void
    {
        $definicion = $this->definicionSlots($partido->formato);

        $roles = RolPartido::where('esActivo', true)
            ->whereIn('nombre', array_keys($definicion))
            ->get()
            ->keyBy('nombre');

        foreach ($definicion as $nombreRol => $cantidad) {
            $rol = $roles->get($nombreRol);

            if ($rol === null) {
                Log::warning("crearSlotsPartido: rol '{$nombreRol}' no existe o está inactivo. idPartido={$partido->idPartido}");
                continue;
            }

            for ($n = 1; $n <= $cantidad; $n++) {
                SlotDesignacion::firstOrCreate([
                    'idPartido'  => $partido->idPartido,
                    'idRol'      => $rol->idRol,
                    'numeroSlot' => $n,
                ]);
            }
        }
    }

    /**
     * Garantiza que el partido tenga slots: los crea y vincula las designaciones
     * existentes (partidos creados antes del sistema de slots).
     */
    private function asegurarSlots(Partido $partido): void
    {
        if (SlotDesignacion::where('idPartido', $partido->idPartido)->exists()) {
            return;
        }

        $this->crearSlotsPartido($partido);

        $designaciones = $partido->designaciones()
            ->whereIn('estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->get();

        foreach ($designaciones as $designacion) {
            SlotDesignacion::where('idPartido', $partido->idPartido)
                ->where('idRol', $designacion->idRol)
                ->whereNull('idDesignacion')
                ->orderBy('numeroSlot')
                ->limit(1)
                ->update(['idDesignacion' => $designacion->idDesignacion]);
        }
    }

    /**
     * Calcula la compensación del árbitro para su designación según la tarifa
     * del torneo (división + rol + formato) y la modalidad de pago del partido.
     *
     * @return array{valor: float|null, modalidad: string|null}
     */
    private function calcularPago(Designacion $designacion): array
    {
        $partido = $designacion->partido;
        $valor   = null;

        if ($partido !== null && $partido->idDivision && $partido->idFormato && $designacion->idRol) {
            $valor = TarifaTorneo::where('idDivision', $partido->idDivision)
                ->where('idRol', $designacion->idRol)
                ->where('idFormato', $partido->idFormato)
                ->value('valorPago');
        }

        return [
            'valor'     => $valor !== null ? (float) $valor : null,
            'modalidad' => $partido?->modalidadPago,
        ];
    }
}
