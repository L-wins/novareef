<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Models\Arbitro;
use App\Models\Torneo;
use App\Services\EstadisticasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EstadisticasController extends Controller
{
    use ResuelveColegio;

    private const SEMANAS_VENTANA_DEFECTO = 8;

    public function __construct(
        private readonly EstadisticasService $estadisticas,
    ) {}

    public function index(Request $request): View
    {
        $idColegio = $this->idColegioActivo();

        return view('designaciones.estadisticas.index', [
            'resumen'      => $this->estadisticas->resumenGeneral($idColegio),
            'categorias'   => $this->estadisticas->arbitrosPorCategoria($idColegio),
            'calificaciones' => $this->estadisticas->rankingCalificaciones($idColegio),
            'torneos'      => $this->torneosDelColegio($idColegio),
            'arbitrosOpciones' => $this->arbitrosDelColegio($idColegio),
        ] + $this->datosDisponibilidad($request, $idColegio)
          + $this->datosPartidosArbitro($request, $idColegio)
          + $this->datosConfiabilidad($request, $idColegio)
          + $this->datosCoincidencias($request, $idColegio));
    }

    // ── Sub-acciones AJAX ──────────────────

    public function disponibilidad(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('designaciones.estadisticas', $request->query());
        }

        $datos = $this->datosDisponibilidad($request, $this->idColegioActivo());

        return response()->json([
            'regions' => [
                'disponibilidad' => view('designaciones.estadisticas.partials.resultados.disponibilidad', $datos)->render(),
            ],
        ]);
    }

    public function partidosArbitro(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('designaciones.estadisticas', $request->query());
        }

        $datos = $this->datosPartidosArbitro($request, $this->idColegioActivo());

        return response()->json([
            'regions' => [
                'partidosArbitro' => view('designaciones.estadisticas.partials.resultados.partidos-arbitro', $datos)->render(),
            ],
        ]);
    }

    public function confiabilidad(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('designaciones.estadisticas', $request->query());
        }

        $datos = $this->datosConfiabilidad($request, $this->idColegioActivo());

        return response()->json([
            'regions' => [
                'confiabilidad' => view('designaciones.estadisticas.partials.resultados.confiabilidad', $datos)->render(),
            ],
        ]);
    }

    public function coincidencias(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('designaciones.estadisticas', $request->query());
        }

        $datos = $this->datosCoincidencias($request, $this->idColegioActivo());

        return response()->json([
            'regions' => [
                'coincidencias' => view('designaciones.estadisticas.partials.resultados.coincidencias', $datos)->render(),
            ],
        ]);
    }

    // ── Filtros + datos por sección (compartidos entre index() y las sub-acciones AJAX) ─

    /** @return array{dispDesde: string, dispHasta: string, dispNombre: string, rankingDisponibilidad: \Illuminate\Support\Collection} */
    private function datosDisponibilidad(Request $request, int $idColegio): array
    {
        [$desde, $hasta] = $this->rangoFechas($request, 'dispDesde', 'dispHasta');
        $nombre = trim((string) $request->query('dispNombre', ''));

        return [
            'dispDesde'             => $desde->toDateString(),
            'dispHasta'             => $hasta->toDateString(),
            'dispNombre'            => $nombre,
            'rankingDisponibilidad' => $this->estadisticas->rankingDisponibilidad($idColegio, $desde, $hasta, $nombre ?: null),
        ];
    }

    /** @return array{idsTorneos: array<int, int>, partidosPorArbitro: \Illuminate\Support\Collection} */
    private function datosPartidosArbitro(Request $request, int $idColegio): array
    {
        $idsTorneos = array_values(array_filter(array_map('intval', (array) $request->query('torneos', []))));

        return [
            'idsTorneos'         => $idsTorneos,
            'partidosPorArbitro' => $this->estadisticas->partidosFinalizadosPorArbitro($idColegio, $idsTorneos),
        ];
    }

    /** @return array{confDesde: string, confHasta: string, confiabilidad: \Illuminate\Support\Collection} */
    private function datosConfiabilidad(Request $request, int $idColegio): array
    {
        [$desde, $hasta] = $this->rangoFechas($request, 'confDesde', 'confHasta');

        return [
            'confDesde'     => $desde->toDateString(),
            'confHasta'     => $hasta->toDateString(),
            'confiabilidad' => $this->estadisticas->confiabilidad($idColegio, $desde, $hasta),
        ];
    }

    /** @return array{idsArbitrosSeleccionados: array<int, int>, coincidencias: array} */
    private function datosCoincidencias(Request $request, int $idColegio): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->query('arbitros', []))));

        return [
            'idsArbitrosSeleccionados' => $ids,
            'coincidencias'            => count($ids) >= 2
                ? $this->estadisticas->coincidencias($idColegio, $ids)
                : ['arbitros' => collect(), 'partidos' => collect(), 'roles' => [], 'pares' => collect()],
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rangoFechas(Request $request, string $paramDesde, string $paramHasta): array
    {
        $desde = $request->query($paramDesde);
        $hasta = $request->query($paramHasta);

        try {
            $desde = $desde ? Carbon::createFromFormat('Y-m-d', $desde)->startOfDay() : null;
            $hasta = $hasta ? Carbon::createFromFormat('Y-m-d', $hasta)->startOfDay() : null;
        } catch (\Throwable) {
            $desde = $hasta = null;
        }

        return [
            $desde ?? now()->subWeeks(self::SEMANAS_VENTANA_DEFECTO)->startOfDay(),
            $hasta ?? now()->startOfDay(),
        ];
    }

    private function torneosDelColegio(int $idColegio)
    {
        return Torneo::where('idColegio', $idColegio)
            ->orderByDesc('temporada')
            ->orderBy('nombreTorneo')
            ->get(['idTorneo', 'nombreTorneo', 'temporada']);
    }

    private function arbitrosDelColegio(int $idColegio)
    {
        return Arbitro::where('idColegio', $idColegio)
            ->where('estadoArbitro', 'activo')
            ->with('usuario')
            ->get(['idArbitro', 'idUsuario'])
            ->sortBy(fn (Arbitro $a) => $a->usuario?->nombreUsuario ?? '')
            ->values();
    }
}
