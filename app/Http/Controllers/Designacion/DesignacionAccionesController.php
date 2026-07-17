<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Exceptions\OptimisticLockException;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\AsignarArbitroRequest;
use App\Http\Requests\Designacion\AsignarVeedorRequest;
use App\Http\Requests\Designacion\CambiarEstadoPartidoRequest;
use App\Http\Requests\Designacion\ReasignarArbitroRequest;
use App\Models\Designacion;
use App\Models\Partido;
use App\Services\CandidatosDesignacionService;
use App\Services\DesignacionService;
use App\StateMachines\PartidoStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Acciones sobre un partido ya creado: asignar/reasignar/quitar árbitro,
 * cambiar estado, veedor. Separado de DesignacionController (que se quedó
 * con el CRUD del partido en sí) porque ese archivo superaba las ~700
 * líneas documentadas — ver auditoría de plataforma, punto 3.1.
 */
class DesignacionAccionesController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DesignacionService $designaciones,
        private readonly CandidatosDesignacionService $candidatos,
    ) {}

    /**
     * Asigna un árbitro a un rol en el partido con optimistic locking.
     */
    public function asignarArbitro(AsignarArbitroRequest $request, int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

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
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            // A diferencia de RuntimeException (regla de negocio esperada,
            // mensaje seguro para el usuario), esto es un bug real — no debe
            // filtrarse crudo al frontend ni quedar invisible en logs/Sentry.
            report($e);

            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado al asignar el árbitro. Intenta de nuevo.'], 500);
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
     * Reemplaza el árbitro de un rol en un partido ya publicado (programado,
     * confirmado o crítico) sin afectar el estado del partido ni las demás
     * designaciones. Solo se notifica al árbitro nuevo.
     */
    public function reasignarArbitro(ReasignarArbitroRequest $request, int $designacionId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

        $designacion = Designacion::where('idDesignacion', $designacionId)
            ->where('idColegio', $idColegio)
            ->firstOrFail();

        try {
            $result = $this->designaciones->reasignarArbitro(
                $designacion,
                (int) $validated['idArbitro'],
                $idColegio,
                Auth::id(),
            );

            return response()->json([
                'success'      => true,
                'advertencias' => $result['advertencias'],
                'designacion'  => $result['designacion'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado al reasignar el árbitro. Intenta de nuevo.'], 500);
        }
    }

    /**
     * Cambia el estado del partido usando la state machine.
     */
    public function cambiarEstadoPartido(CambiarEstadoPartidoRequest $request, int $id): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

        $partido     = Partido::where('idColegio', $idColegio)->findOrFail($id);
        $estadoNuevo = $validated['estadoNuevo'];

        if ($error = $this->errorTransicionManualAProgramado($partido, $estadoNuevo)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        abort_unless($this->puedeCambiarEstadoPartidoA($estadoNuevo), 403);

        try {
            PartidoStateMachine::transicionarCon($partido, $estadoNuevo, Auth::user(), $validated['detalle'] ?? null);

            return response()->json(['success' => true]);
        } catch (OptimisticLockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * 'programado' solo es un destino manual válido para reactivar un
     * aplazado o revertir un finalizado — nunca para "deshacer" un
     * confirmado/crítico sin resolver la causa real (eso lo hace el propio
     * sistema al reasignar y confirmar el rol pendiente).
     */
    private function errorTransicionManualAProgramado(Partido $partido, string $estadoNuevo): ?string
    {
        if ($estadoNuevo !== Partido::ESTADO_PROGRAMADO) {
            return null;
        }

        $origenesValidos = [Partido::ESTADO_APLAZADO, Partido::ESTADO_FINALIZADO];

        return in_array($partido->estadoPartido, $origenesValidos, true)
            ? null
            : 'No se puede revertir manualmente a programado desde este estado.';
    }

    /** Cancelar es exclusivo de ejecutivo/admin; finalizar y aplazar los puede hacer también el designador. */
    private function puedeCambiarEstadoPartidoA(string $estadoNuevo): bool
    {
        $user = Auth::user();

        if ($estadoNuevo === Partido::ESTADO_CANCELADO) {
            return $user->hasRole('ejecutivo') || $user->rolUsuario === 'superadmin';
        }

        if (in_array($estadoNuevo, [Partido::ESTADO_FINALIZADO, Partido::ESTADO_APLAZADO], true)) {
            return $user->hasRole('ejecutivo') || $user->hasRole('designador') || $user->rolUsuario === 'superadmin';
        }

        return true;
    }

    /**
     * Retorna árbitros disponibles para un partido con todos sus indicadores (AJAX).
     */
    public function getArbitrosDisponibles(int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        return response()->json($this->candidatos->candidatosParaPartido($partido, $idColegio));
    }

    /**
     * Asigna (o cambia) el veedor de un partido.
     */
    public function asignarVeedor(AsignarVeedorRequest $request, int $partidoId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

        $partido = Partido::where('idColegio', $idColegio)->findOrFail($partidoId);

        $idVeedor = isset($validated['idVeedor']) ? (int) $validated['idVeedor'] : null;

        try {
            $this->designaciones->asignarVeedor($partido, $idVeedor, $idColegio, Auth::id());
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        return response()->json(['success' => true]);
    }
}
