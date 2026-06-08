<?php

declare(strict_types=1);

namespace App\Http\Controllers\Designacion;

use App\Actions\NotificarDesignadorIndisponibilidad;
use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Designacion\IndisponibilidadExtraordinariaRequest;
use App\Http\Requests\Designacion\MarcarNoDisponibleRequest;
use App\Http\Requests\Designacion\StoreDisponibilidadRequest;
use App\Models\Designacion;
use App\Models\DisponibilidadArbitro;
use App\Models\IndisponibilidadExtraordinaria;
use App\Support\SemanaNavegacion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly NotificarDesignadorIndisponibilidad $notificar,
    ) {}

    // ── index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $arbitro   = $this->arbitroAutenticado();
        $semana    = SemanaNavegacion::desde(null);
        $idColegio = $this->idColegioActivo();

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

        $yaGuardo = DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '>=', $semana->lunes->toDateString())
            ->exists();

        return view('disponibilidad.index', [
            'arbitro'            => $arbitro,
            'semana'             => $semana,
            'disponibilidades'   => $disponibilidades,
            'indisponibilidades' => $indisponibilidades,
            'franjas'            => DisponibilidadArbitro::getFranjas(),
            'yaGuardo'           => $yaGuardo,
            'diaCiclo'           => \App\Models\ConfiguracionColegio::getDiaDisponibilidad($idColegio),
            'nombreDia'          => \App\Models\ConfiguracionColegio::getNombreDia(
                \App\Models\ConfiguracionColegio::getDiaDisponibilidad($idColegio)
            ),
        ]);
    }

    // ── store (AJAX) ──────────────────────────────────────────────────────────

    public function store(StoreDisponibilidadRequest $request): JsonResponse
    {
        $arbitro  = $this->arbitroAutenticado();
        $inicioSemana = SemanaNavegacion::desde(null)->lunes;

        if (DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', '>=', $inicioSemana->toDateString())
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya guardaste tu disponibilidad para esta semana. Podrás modificarla la próxima semana.',
            ], 422);
        }

        DB::transaction(function () use ($request, $arbitro): void {
            foreach ($request->validated('disponibilidades') as $item) {
                $fecha  = $item['fecha'];
                $franja = $item['franja'] ?? null;

                if ($franja !== null && $franja !== '') {
                    DisponibilidadArbitro::updateOrCreate(
                        ['idArbitro' => $arbitro->idArbitro, 'fechaDisponibilidad' => $fecha],
                        ['franjaHoraria' => $franja, 'motivo' => null],
                    );
                } else {
                    DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
                        ->where('fechaDisponibilidad', $fecha)
                        ->delete();
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Disponibilidad guardada correctamente.']);
    }

    // ── marcarNoDisponible (AJAX DELETE) ──────────────────────────────────────

    public function marcarNoDisponible(MarcarNoDisponibleRequest $request, string $fecha): JsonResponse
    {
        $arbitro = $this->arbitroAutenticado();

        DisponibilidadArbitro::where('idArbitro', $arbitro->idArbitro)
            ->where('fechaDisponibilidad', $fecha)
            ->delete();

        $designacionesAfectadas = $this->designacionesConfirmadasEnFecha($arbitro->idArbitro, $fecha);

        if ($designacionesAfectadas->isNotEmpty()) {
            IndisponibilidadExtraordinaria::create([
                'idArbitro'         => $arbitro->idArbitro,
                'idColegio'         => $this->idColegioActivo(),
                'fechaAfectada'     => $fecha,
                'franjaAfectada'    => DisponibilidadArbitro::FRANJA_TODO_DIA,
                'motivo'            => $request->validated('motivo'),
                'idUsuarioRegistro' => Auth::id(),
            ]);

            $this->notificar->ejecutar(
                $arbitro, $fecha,
                DisponibilidadArbitro::FRANJA_TODO_DIA,
                $request->validated('motivo'),
                $designacionesAfectadas,
            );

            return response()->json([
                'success' => true,
                'afecta'  => true,
                'message' => 'Disponibilidad eliminada. Se notificó al designador por partidos confirmados en esa fecha.',
            ]);
        }

        return response()->json(['success' => true, 'afecta' => false, 'message' => 'Disponibilidad eliminada.']);
    }

    // ── indisponibilidadExtraordinaria ────────────────────────────────────────

    public function indisponibilidadExtraordinaria(IndisponibilidadExtraordinariaRequest $request): RedirectResponse
    {
        $datos   = $request->validated();
        $arbitro = $this->arbitroAutenticado();

        IndisponibilidadExtraordinaria::create([
            'idArbitro'         => $arbitro->idArbitro,
            'idColegio'         => $this->idColegioActivo(),
            'fechaAfectada'     => $datos['fechaAfectada'],
            'franjaAfectada'    => $datos['franjaAfectada'],
            'motivo'            => $datos['motivo'],
            'idUsuarioRegistro' => Auth::id(),
        ]);

        $designacionesAfectadas = $this->designacionesConfirmadasEnFecha($arbitro->idArbitro, $datos['fechaAfectada']);

        $this->notificar->ejecutar($arbitro, $datos['fechaAfectada'], $datos['franjaAfectada'], $datos['motivo'], $designacionesAfectadas);

        $sufijo = $designacionesAfectadas->isNotEmpty() ? ' El designador fue notificado.' : '';

        return back()->with('success', 'Indisponibilidad extraordinaria registrada correctamente.' . $sufijo);
    }

    // ── verDisponibilidad (AJAX — para designador) ────────────────────────────

    public function verDisponibilidad(int $arbitroId): JsonResponse
    {
        $arbitro = \App\Models\Arbitro::with('usuario')
            ->where('idArbitro', $arbitroId)
            ->where('idColegio', $this->idColegioActivo())
            ->firstOrFail();

        $hoy    = Carbon::today();
        $limite = $hoy->copy()->addWeeks(2)->endOfWeek(Carbon::SUNDAY);
        $rango  = [$hoy->toDateString(), $limite->toDateString()];

        $disponibilidades = DisponibilidadArbitro::where('idArbitro', $arbitroId)
            ->whereBetween('fechaDisponibilidad', $rango)
            ->orderBy('fechaDisponibilidad')
            ->get()
            ->map(fn ($d) => [
                'fecha'       => $d->fechaDisponibilidad->format('Y-m-d'),
                'franja'      => $d->franjaHoraria,
                'franjaLabel' => $d->franjaLegible(),
            ]);

        $indisponibilidades = IndisponibilidadExtraordinaria::where('idArbitro', $arbitroId)
            ->whereBetween('fechaAfectada', $rango)
            ->orderBy('fechaAfectada')
            ->get()
            ->map(fn ($i) => [
                'fecha'  => $i->fechaAfectada->format('Y-m-d'),
                'franja' => $i->franjaAfectada,
                'motivo' => $i->motivo,
            ]);

        return response()->json([
            'arbitro'            => ['idArbitro' => $arbitro->idArbitro, 'nombre' => $arbitro->usuario?->nombreUsuario ?? '—'],
            'disponibilidad'     => $disponibilidades,
            'indisponibilidades' => $indisponibilidades,
        ]);
    }

    // ── Helper privado ────────────────────────────────────────────────────────

    /**
     * Retorna las designaciones confirmadas del árbitro en una fecha dada.
     * Centraliza la query usada en marcarNoDisponible e indisponibilidadExtraordinaria.
     */
    private function designacionesConfirmadasEnFecha(int $idArbitro, string $fecha): \Illuminate\Database\Eloquent\Collection
    {
        return Designacion::where('idArbitro', $idArbitro)
            ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA)
            ->whereHas('partido', fn ($q) => $q->whereDate('fechaPartido', $fecha))
            ->with('partido.torneo')
            ->get();
    }
}
