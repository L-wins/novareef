<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\Designacion;
use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Consultas de lectura/agregación de Designaciones (resúmenes de dashboard
 * por rol). Las operaciones de escritura (crear partido, asignar, confirmar,
 * etc.) viven en DesignacionService — mismo criterio de separación que ya
 * usa Finanzas (FinanzasService / ReporteFinanzasService).
 */
final class ReporteDesignacionesService
{
    /**
     * Resumen para el dashboard de designador/ejecutivo: partidos críticos y
     * de hoy (colegio completo) + una vista previa de designaciones aún sin
     * confirmar por el árbitro.
     *
     * @return array{criticosCount: int, hoyCount: int, pendientesConfirmacion: Collection<int, Designacion>}
     */
    public function resumenParaDashboard(int $idColegio, int $limitePendientes = 5): array
    {
        $criticosCount = Partido::where('idColegio', $idColegio)
            ->where('estadoPartido', Partido::ESTADO_CRITICO)
            ->count();

        $hoyCount = Partido::where('idColegio', $idColegio)
            ->whereDate('fechaPartido', now()->toDateString())
            ->count();

        $pendientesConfirmacion = Designacion::query()
            ->select('designaciones.*')
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->where('designaciones.idColegio', $idColegio)
            ->where('designaciones.estadoDesignacion', Designacion::ESTADO_PENDIENTE)
            ->where('partidos.estadoPartido', '!=', Partido::ESTADO_BORRADOR)
            ->orderBy('partidos.fechaPartido')
            ->orderBy('partidos.horaPartido')
            ->with(['partido.torneo', 'arbitro.usuario', 'rol'])
            ->limit($limitePendientes)
            ->get();

        return compact('criticosCount', 'hoyCount', 'pendientesConfirmacion');
    }

    /**
     * Grid de torneos del colegio con conteo de partidos totales, críticos y
     * de hoy — para DesignacionController::index() cuando no hay ?torneo=.
     *
     * @return Collection<int, Torneo>
     */
    public function gridTorneosConConteos(int $idColegio): Collection
    {
        return Torneo::where('idColegio', $idColegio)
            ->withCount([
                'partidos',
                'partidos as partidos_criticos_count' => fn ($q) => $q->where('estadoPartido', Partido::ESTADO_CRITICO),
                'partidos as partidos_hoy_count' => fn ($q) => $q->whereDate('fechaPartido', now()->toDateString()),
            ])
            ->orderByDesc('temporada')
            ->orderBy('nombreTorneo')
            ->get();
    }

    /** Cantidad de partidos en estado crítico del colegio, opcionalmente acotado a un torneo. */
    public function criticosCount(int $idColegio, ?int $idTorneo = null): int
    {
        return Partido::where('idColegio', $idColegio)
            ->where('estadoPartido', Partido::ESTADO_CRITICO)
            ->when($idTorneo !== null, fn ($q) => $q->where('idTorneo', $idTorneo))
            ->count();
    }

    /**
     * Listado paginado de partidos de un torneo con filtros de estado/fecha/
     * división — para DesignacionController::index() cuando hay ?torneo=X.
     *
     * @param  array{estado?: ?string, fecha?: ?string, division?: ?int}  $filtros
     */
    public function listadoPartidosDeTorneo(int $idColegio, Torneo $torneo, array $filtros): LengthAwarePaginator
    {
        return Partido::where('idColegio', $idColegio)
            ->where('idTorneo', $torneo->idTorneo)
            ->with([
                'torneo',
                'division',
                'sede',
                'formato',
                'designaciones.arbitro.usuario',
                'designaciones.rol',
            ])
            ->when(! empty($filtros['estado']), fn ($q) => $q->where('estadoPartido', $filtros['estado']))
            ->when(! empty($filtros['fecha']), fn ($q) => $q->whereDate('fechaPartido', $filtros['fecha']))
            ->when(! empty($filtros['division']), fn ($q) => $q->where('idDivision', $filtros['division']))
            ->orderBy('fechaPartido', 'asc')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Próximas designaciones (pendientes o confirmadas) del árbitro autenticado
     * a partir de hoy — versión liviana de lo que ya calcula
     * MisPartidosController::misPartidos() para la vista completa, pensada
     * para un widget de dashboard (sin cargar el historial completo del árbitro).
     *
     * @return Collection<int, Designacion>
     */
    public function proximasDesignacionesArbitro(Arbitro $arbitro, int $limite = 5): Collection
    {
        return Designacion::query()
            ->select('designaciones.*')
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->where('designaciones.idArbitro', $arbitro->idArbitro)
            ->whereIn('designaciones.estadoDesignacion', [Designacion::ESTADO_PENDIENTE, Designacion::ESTADO_CONFIRMADA])
            ->where('partidos.estadoPartido', '!=', Partido::ESTADO_BORRADOR)
            ->where('partidos.fechaPartido', '>=', now()->toDateString())
            ->orderBy('partidos.fechaPartido')
            ->orderBy('partidos.horaPartido')
            ->with(['partido.torneo', 'rol'])
            ->limit($limite)
            ->get();
    }

    /**
     * Partidos finalizados asignados a este veedor que aún tienen alguna
     * designación confirmada sin calificar — cola de trabajo del rol `veedor`.
     *
     * @return Collection<int, Partido>
     */
    public function partidosPendientesDeCalificar(int $idVeedor, int $limite = 10): Collection
    {
        return Partido::where('idVeedor', $idVeedor)
            ->where('estadoPartido', Partido::ESTADO_FINALIZADO)
            ->whereHas('designaciones', fn ($q) => $q->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
                ->whereDoesntHave('calificacion'))
            ->with('torneo')
            ->orderByDesc('fechaPartido')
            ->limit($limite)
            ->get();
    }
}
