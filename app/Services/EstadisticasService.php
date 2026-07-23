<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Arbitro;
use App\Models\CalificacionArbitro;
use App\Models\CategoriaArbitro;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\Partido;
use App\Models\RolPartido;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de agregación para el módulo "Estadísticas" de Designaciones —
 * todas de solo lectura. Cada método recibe el idColegio y los filtros ya
 * resueltos (nunca el Request), mismo criterio que ReporteFinanzasService/
 * ReporteDesignacionesService. Multi-tenant: siempre idColegio directo en la
 * tabla principal de cada query; DisponibilidadArbitro y CalificacionArbitro
 * no tienen idColegio propio, así que ahí se resuelve primero la lista de
 * ids del colegio (arbitros/designaciones) o se hace join.
 */
final class EstadisticasService
{
    /** Mínimo de muestras para que un árbitro aparezca en confiabilidad/calificación — evita rankear con 1 dato suelto. */
    private const MINIMO_MUESTRAS = 3;

    // ── 1. Resumen general ────────────────

    /** @return array{partidosPorEstado: Collection, arbitrosActivos: int, totalCategorias: int} */
    public function resumenGeneral(int $idColegio): array
    {
        $partidosPorEstado = Partido::where('idColegio', $idColegio)
            ->selectRaw('estadoPartido, COUNT(*) as total')
            ->groupBy('estadoPartido')
            ->pluck('total', 'estadoPartido');

        return [
            'partidosPorEstado' => $partidosPorEstado,
            'arbitrosActivos'   => Arbitro::where('idColegio', $idColegio)->where('estadoArbitro', 'activo')->count(),
            'totalCategorias'   => CategoriaArbitro::where('idColegio', $idColegio)->count(),
        ];
    }

    // ── 2. Árbitros por categoría ─────────

    public function arbitrosPorCategoria(int $idColegio): Collection
    {
        return CategoriaArbitro::where('idColegio', $idColegio)
            ->withCount([
                'arbitros',
                'arbitros as activos_count' => fn ($q) => $q->where('estadoArbitro', 'activo'),
            ])
            ->orderBy('nombreCategoria')
            ->get();
    }

    // ── 3. Ranking de disponibilidad ──────

    /** @return Collection<int, array{arbitro: Arbitro, diasReportados: int, diasNoDisponible: int, diasSinReportar: int, porcentaje: float}> */
    public function rankingDisponibilidad(int $idColegio, Carbon $desde, Carbon $hasta, ?string $nombre = null): Collection
    {
        $arbitros = Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->with('usuario')
            ->when($nombre !== null && $nombre !== '', function ($q) use ($nombre) {
                $q->whereHas('usuario', fn ($u) => $u->where('nombreUsuario', 'like', '%' . $nombre . '%'));
            })
            ->get();

        $totalDias = (int) $desde->diffInDays($hasta) + 1;

        $agregados = DisponibilidadArbitro::selectRaw(
            "idArbitro,
             COUNT(*) as diasReportados,
             SUM(CASE WHEN franjaHoraria = ? THEN 1 ELSE 0 END) as diasNoDisponible",
            [DisponibilidadArbitro::FRANJA_NO_DISPONIBLE]
        )
            ->whereIn('idArbitro', $arbitros->pluck('idArbitro'))
            ->whereBetween('fechaDisponibilidad', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('idArbitro')
            ->get()
            ->keyBy('idArbitro');

        return $arbitros->map(function (Arbitro $arbitro) use ($agregados, $totalDias) {
            $fila             = $agregados->get($arbitro->idArbitro);
            $diasReportados   = (int) ($fila->diasReportados ?? 0);
            $diasNoDisponible = (int) ($fila->diasNoDisponible ?? 0);
            $diasDisponible   = $diasReportados - $diasNoDisponible;

            return [
                'arbitro'          => $arbitro,
                'diasReportados'   => $diasReportados,
                'diasNoDisponible' => $diasNoDisponible,
                'diasSinReportar'  => max(0, $totalDias - $diasReportados),
                'porcentaje'       => $totalDias > 0 ? round($diasDisponible / $totalDias * 100, 1) : 0.0,
            ];
        })->sortByDesc('porcentaje')->values();
    }

    // ── 4. Partidos finalizados por árbitro (con desglose de rol) ─

    /** @return Collection<int, array{arbitro: Arbitro, total: int, porRol: array<string, int>}> */
    public function partidosFinalizadosPorArbitro(int $idColegio, array $idsTorneos = []): Collection
    {
        $nombresRol = RolPartido::pluck('nombre', 'idRol');

        $filas = Designacion::query()
            ->join('partidos', 'partidos.idPartido', '=', 'designaciones.idPartido')
            ->where('designaciones.idColegio', $idColegio)
            ->where('partidos.estadoPartido', Partido::ESTADO_FINALIZADO)
            ->when($idsTorneos !== [], fn ($q) => $q->whereIn('partidos.idTorneo', $idsTorneos))
            ->groupBy('designaciones.idArbitro', 'designaciones.idRol')
            ->selectRaw('designaciones.idArbitro, designaciones.idRol, COUNT(*) as total')
            ->get();

        if ($filas->isEmpty()) {
            return collect();
        }

        $arbitros = Arbitro::whereIn('idArbitro', $filas->pluck('idArbitro')->unique())
            ->with('usuario')
            ->get()
            ->keyBy('idArbitro');

        return $filas->groupBy('idArbitro')
            ->map(function (Collection $filasArbitro, int $idArbitro) use ($arbitros, $nombresRol) {
                $porRol = $filasArbitro->mapWithKeys(
                    fn ($f) => [($nombresRol[$f->idRol] ?? "Rol #{$f->idRol}") => (int) $f->total]
                )->all();

                return [
                    'arbitro' => $arbitros->get($idArbitro),
                    'total'   => $filasArbitro->sum('total'),
                    'porRol'  => $porRol,
                ];
            })
            ->filter(fn ($fila) => $fila['arbitro'] !== null)
            ->sortByDesc('total')
            ->values();
    }

    // ── 5. Confiabilidad (tasa de rechazo + tiempo de confirmación) ─

    /** @return Collection<int, array{arbitro: Arbitro, total: int, rechazadas: int, porcentajeRechazo: float, minutosPromedioConfirmacion: ?float}> */
    public function confiabilidad(int $idColegio, Carbon $desde, Carbon $hasta): Collection
    {
        $filas = Designacion::where('idColegio', $idColegio)
            ->whereBetween('created_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->selectRaw(
                "idArbitro,
                 COUNT(*) as total,
                 SUM(CASE WHEN estadoDesignacion = ? THEN 1 ELSE 0 END) as rechazadas,
                 AVG(CASE WHEN estadoDesignacion = ? AND fechaConfirmacion IS NOT NULL
                          THEN TIMESTAMPDIFF(MINUTE, created_at, fechaConfirmacion) END) as minutosPromedioConfirmacion",
                [Designacion::ESTADO_RECHAZADA, Designacion::ESTADO_CONFIRMADA]
            )
            ->groupBy('idArbitro')
            ->havingRaw('COUNT(*) >= ?', [self::MINIMO_MUESTRAS])
            ->get();

        if ($filas->isEmpty()) {
            return collect();
        }

        $arbitros = Arbitro::whereIn('idArbitro', $filas->pluck('idArbitro'))->with('usuario')->get()->keyBy('idArbitro');

        return $filas->map(function ($f) use ($arbitros) {
            $arbitro = $arbitros->get($f->idArbitro);

            return $arbitro === null ? null : [
                'arbitro'                      => $arbitro,
                'total'                         => (int) $f->total,
                'rechazadas'                    => (int) $f->rechazadas,
                'porcentajeRechazo'             => round(((int) $f->rechazadas) / ((int) $f->total) * 100, 1),
                'minutosPromedioConfirmacion'   => $f->minutosPromedioConfirmacion !== null ? round((float) $f->minutosPromedioConfirmacion, 0) : null,
            ];
        })->filter()->sortByDesc('porcentajeRechazo')->values();
    }

    // ── 6. Calificación promedio (veedor) ─

    /** @return Collection<int, array{arbitro: Arbitro, promedio: float, total: int}> */
    public function rankingCalificaciones(int $idColegio): Collection
    {
        $filas = CalificacionArbitro::query()
            ->join('designaciones', 'designaciones.idDesignacion', '=', 'calificaciones_arbitro.idDesignacion')
            ->where('calificaciones_arbitro.idColegio', $idColegio)
            ->groupBy('designaciones.idArbitro')
            ->havingRaw('COUNT(*) >= ?', [self::MINIMO_MUESTRAS])
            ->selectRaw('designaciones.idArbitro, AVG(calificaciones_arbitro.nota) as promedio, COUNT(*) as total')
            ->orderByDesc('promedio')
            ->get();

        if ($filas->isEmpty()) {
            return collect();
        }

        $arbitros = Arbitro::whereIn('idArbitro', $filas->pluck('idArbitro'))->with('usuario')->get()->keyBy('idArbitro');

        return $filas->map(fn ($f) => $arbitros->get($f->idArbitro) === null ? null : [
            'arbitro'  => $arbitros->get($f->idArbitro),
            'promedio' => round((float) $f->promedio, 2),
            'total'    => (int) $f->total,
        ])->filter()->values();
    }

    // ── 7. Coincidencias entre árbitros seleccionados ─

    /**
     * @return array{
     *     arbitros: Collection<int, Arbitro>,
     *     partidos: Collection<int, Partido>,
     *     roles: array<int, array<int, string>>,
     *     pares: Collection<int, array{a: Arbitro, b: Arbitro, total: int}>,
     * }
     */
    public function coincidencias(int $idColegio, array $idsArbitros): array
    {
        $idsArbitros = array_values(array_unique(array_map('intval', $idsArbitros)));

        $arbitros = Arbitro::whereIn('idArbitro', $idsArbitros)
            ->where('idColegio', $idColegio)
            ->with('usuario')
            ->get();

        $vacio = ['arbitros' => $arbitros, 'partidos' => collect(), 'roles' => [], 'pares' => collect()];

        if ($arbitros->count() < 2) {
            return $vacio;
        }

        $idsValidos = $arbitros->pluck('idArbitro')->all();

        // Todas las designaciones (no rechazadas) de CUALQUIERA de los
        // seleccionados, agrupadas por partido — base única para calcular
        // tanto la coincidencia "todos juntos" como el desglose por pares,
        // sin repetir la query.
        $porPartidoTodos = Designacion::where('idColegio', $idColegio)
            ->whereIn('idArbitro', $idsValidos)
            ->where('estadoDesignacion', '!=', Designacion::ESTADO_RECHAZADA)
            ->get(['idPartido', 'idArbitro', 'idRol'])
            ->groupBy('idPartido');

        if ($porPartidoTodos->isEmpty()) {
            return $vacio;
        }

        // ── Desglose por pares — cuenta, para cada combinación de 2
        // seleccionados, en cuántos partidos coincidieron ambos (sin exigir
        // que el resto del grupo también haya estado presente). Mucho más
        // accionable que el conteo estricto de "todos a la vez" cuando se
        // eligen 3 o más árbitros.
        $conteosPares = [];
        foreach ($porPartidoTodos as $designacionesPartido) {
            $presentes = $designacionesPartido->pluck('idArbitro')->unique()->values();

            foreach ($presentes as $i => $idA) {
                foreach ($presentes as $j => $idB) {
                    if ($j <= $i) {
                        continue;
                    }
                    $clave = $idA < $idB ? "{$idA}-{$idB}" : "{$idB}-{$idA}";
                    $conteosPares[$clave] = ($conteosPares[$clave] ?? 0) + 1;
                }
            }
        }

        $arbitrosPorId = $arbitros->keyBy('idArbitro');
        $pares = collect($conteosPares)
            ->map(function (int $total, string $clave) use ($arbitrosPorId) {
                [$idA, $idB] = array_map('intval', explode('-', $clave));

                return ['a' => $arbitrosPorId->get($idA), 'b' => $arbitrosPorId->get($idB), 'total' => $total];
            })
            ->sortByDesc('total')
            ->values();

        // ── Coincidencia estricta: partidos donde aparecen TODOS los
        // seleccionados a la vez.
        $porPartidoTodosJuntos = $porPartidoTodos->filter(
            fn (Collection $g) => $g->pluck('idArbitro')->unique()->count() === count($idsValidos)
        );

        $nombresRol = RolPartido::pluck('nombre', 'idRol');
        $roles = $porPartidoTodosJuntos->map(
            fn (Collection $g) => $g->mapWithKeys(fn ($d) => [$d->idArbitro => $nombresRol[$d->idRol] ?? "Rol #{$d->idRol}"])->all()
        )->all();

        $partidos = $porPartidoTodosJuntos->isEmpty()
            ? collect()
            : Partido::whereIn('idPartido', $porPartidoTodosJuntos->keys())
                ->with('torneo')
                ->orderByDesc('fechaPartido')
                ->get();

        return ['arbitros' => $arbitros, 'partidos' => $partidos, 'roles' => $roles, 'pares' => $pares];
    }
}
