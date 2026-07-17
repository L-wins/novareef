<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\StoreCalificacionRequest;
use App\Models\CalificacionArbitro;
use App\Models\Designacion;
use App\Models\Partido;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalificacionController extends Controller
{
    use ResuelveColegio;

    /**
     * Vista de calificaciones de un partido (veedor/ejecutivo).
     */
    public function index(int $partidoId): View
    {
        $idColegio = $this->idColegioActivo();

        $partido = Partido::where('idColegio', $idColegio)
            ->with([
                'torneo',
                'designaciones' => fn ($q) => $q->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA),
                'designaciones.arbitro.usuario',
                'designaciones.arbitro.categoria',
                'designaciones.rol',
                'designaciones.calificacion',
            ])
            ->findOrFail($partidoId);

        return view('calificaciones.index', compact('partido'));
    }

    /**
     * Registra la calificación de un árbitro por designación (AJAX).
     */
    public function store(StoreCalificacionRequest $request, int $designacionId): JsonResponse
    {
        $idColegio = $this->idColegioActivo();

        $validated = $request->validated();

        $designacion = Designacion::where('idDesignacion', $designacionId)
            ->where('idColegio', $idColegio)
            ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->with('partido', 'arbitro')
            ->firstOrFail();

        // Solo se puede calificar si el partido está finalizado
        abort_unless(
            $designacion->partido->estadoPartido === Partido::ESTADO_FINALIZADO,
            422,
            'Solo se pueden calificar árbitros de partidos finalizados.'
        );

        // Crear o actualizar calificación (única por designación)
        $calificacion = CalificacionArbitro::updateOrCreate(
            ['idDesignacion' => $designacionId],
            [
                'idVeedor'    => Auth::id(),
                'idColegio'   => $idColegio,
                'nota'        => $validated['nota'],
                'comentario'  => $validated['comentario'],
            ]
        );

        // Recalcular score del árbitro como promedio de todas sus calificaciones
        $arbitro = $designacion->arbitro;
        $nuevaScore = CalificacionArbitro::whereHas(
            'designacion',
            fn ($q) => $q->where('idArbitro', $arbitro->idArbitro)
        )->avg('nota');

        $arbitro->update(['scoreDesempeno' => round((float) $nuevaScore, 2)]);

        return response()->json([
            'success'   => true,
            'nuevaScore'=> round((float) $nuevaScore, 2),
            'nota'      => (float) $calificacion->nota,
            'notaLabel' => $calificacion->notaLabel,
            'notaColor' => $calificacion->notaColor,
        ]);
    }
}
