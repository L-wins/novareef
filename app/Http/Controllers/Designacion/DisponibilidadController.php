<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\IndisponibilidadExtraordinariaRequest;
use App\Http\Requests\Designacion\MarcarNoDisponibleRequest;
use App\Http\Requests\Designacion\StoreDisponibilidadRequest;
use App\Models\Arbitro;
use App\Models\ConfiguracionColegio;
use App\Models\DisponibilidadArbitro;
use App\Models\IndisponibilidadExtraordinaria;
use App\Services\DisponibilidadService;
use App\Support\SemanaNavegacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    // ── index ─────

    public function index(): View
    {
        $arbitro   = $this->arbitroAutenticado();
        $idColegio = $this->idColegioActivo();
        $diaCiclo  = ConfiguracionColegio::getDiaDisponibilidad($idColegio);
        $semana    = SemanaNavegacion::desde(null, $diaCiclo);

        $disponibilidades = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->whereBetween('fechaDisponibilidad', [
                $semana->lunes->toDateString(),
                $semana->domingo->toDateString(),
            ])
            ->get()
            ->keyBy(fn ($d) => $d->fechaDisponibilidad->format('Y-m-d'));

        $indisponibilidades = IndisponibilidadExtraordinaria::where('idArbitro', $arbitro->idArbitro)
            ->whereBetween('fechaAfectada', [
                $semana->lunes->toDateString(),
                $semana->domingo->toDateString(),
            ])
            ->get()
            ->groupBy(fn ($i) => $i->fechaAfectada->format('Y-m-d'));

        return view('disponibilidad.index', [
            'arbitro'            => $arbitro,
            'semana'             => $semana,
            'disponibilidades'   => $disponibilidades,
            'indisponibilidades' => $indisponibilidades,
            'franjas'            => DisponibilidadArbitro::getFranjas(),
            'yaGuardo'           => $this->disponibilidad->yaReportoEstaSemana($arbitro),
            'diaCiclo'           => $diaCiclo,
            'nombreDia'          => ConfiguracionColegio::getNombreDia($diaCiclo),
        ]);
    }

    // ── store (AJAX) ──────────────────────

    public function store(StoreDisponibilidadRequest $request): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        try {
            $this->disponibilidad->guardarSemana($arbitro, $request->validated('disponibilidades'));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Disponibilidad guardada correctamente.']);
    }

    // ── marcarNoDisponible (AJAX DELETE) ──

    public function marcarNoDisponible(MarcarNoDisponibleRequest $request, string $fecha): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        $afecta = $this->disponibilidad->marcarNoDisponible(
            $arbitro,
            $fecha,
            $request->validated('motivo'),
            $this->idColegioActivo(),
            Auth::id(),
        );

        return response()->json([
            'success' => true,
            'afecta'  => $afecta,
            'message' => $afecta
                ? 'Disponibilidad eliminada. Se notificó al designador por partidos confirmados en esa fecha.'
                : 'Disponibilidad eliminada.',
        ]);
    }

    // ── indisponibilidadExtraordinaria ────

    public function indisponibilidadExtraordinaria(IndisponibilidadExtraordinariaRequest $request): RedirectResponse
    {
        $datos   = $request->validated();
        $arbitro = $this->arbitroAutenticado();

        $afecta = $this->disponibilidad->registrarIndisponibilidadExtraordinaria(
            $arbitro,
            $datos['fechaAfectada'],
            $datos['franjaAfectada'],
            $datos['motivo'],
            $this->idColegioActivo(),
            Auth::id(),
        );

        $sufijo = $afecta ? ' El designador fue notificado.' : '';

        return back()->with('success', 'Indisponibilidad extraordinaria registrada correctamente.' . $sufijo);
    }

    // ── verDisponibilidad (AJAX — para designador) ────

    public function verDisponibilidad(int $arbitroId): JsonResponse
    {
        $arbitro = Arbitro::with('usuario')
            ->where('idArbitro', $arbitroId)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        $resumen = $this->disponibilidad->resumenParaDesignador($arbitroId);

        return response()->json([
            'arbitro'            => ['idArbitro' => $arbitro->idArbitro, 'nombre' => $arbitro->usuario?->nombreUsuario ?? '—'],
            'disponibilidad'     => $resumen['disponibilidades'],
            'indisponibilidades' => $resumen['indisponibilidades'],
        ]);
    }

    /**
     * Vista de disponibilidad semanal de todo el colegio (designador/ejecutivo)
     * — movido desde DesignacionController, donde estaba fuera de lugar (ese
     * archivo superaba las ~700 líneas documentadas; ver auditoría de
     * plataforma, punto 3.1). Usa los mismos modelos/helpers que el resto de
     * esta clase, solo que agregados para todo el colegio en vez de un árbitro.
     */
    public function general(Request $request): View
    {
        $idColegio = $this->idColegioActivo();
        $semana    = SemanaNavegacion::desde(
            $request->query('semana'),
            ConfiguracionColegio::getDiaDisponibilidad($idColegio),
            recortarAHoy: false,
        );

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
