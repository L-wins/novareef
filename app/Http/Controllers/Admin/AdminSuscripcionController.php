<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CambiarPlanColegioRequest;
use App\Http\Requests\Admin\ExtenderSuscripcionRequest;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Services\AdminAuditService;
use App\Services\SuscripcionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSuscripcionController extends Controller
{
    /** Ventana de "vence pronto" usada en el stat-card y el filtro rápido. */
    private const DIAS_VENCE_PRONTO = 7;

    public function __construct(
        private readonly SuscripcionService $suscripciones,
        private readonly AdminAuditService $auditoria,
    ) {}

    /**
     * Listado transversal de todas las suscripciones (de todos los colegios),
     * con filtros por colegio, estado, plan y proximidad de vencimiento.
     * Orden por defecto: fechaVencimiento ascendente — así lo más urgente
     * (vencidas hace más tiempo, luego lo próximo a vencer) queda arriba,
     * en vez de un orden sin ninguna prioridad de atención.
     */
    public function index(Request $request): View
    {
        $suscripciones = $this->consultaFiltrada($request)->paginate(20)->withQueryString();
        $planes = Plan::orderBy('orden')->get(['idPlan', 'nombre']);

        return view('admin.suscripciones.index', [
            'suscripciones' => $suscripciones,
            'planes' => $planes,
            'resumen' => $this->resumen(),
            'diasVencePronto' => self::DIAS_VENCE_PRONTO,
        ]);
    }

    /** Export CSV del listado filtrado — mismos filtros que index(), sin paginar. */
    public function exportarCsv(Request $request): StreamedResponse
    {
        $suscripciones = $this->consultaFiltrada($request)->get();

        $callback = function () use ($suscripciones): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Colegio', 'Plan', 'Estado', 'Inicio', 'Vencimiento', 'Días restantes']);

            foreach ($suscripciones as $s) {
                $dias = $s->fechaVencimiento ? today()->diffInDays($s->fechaVencimiento, false) : null;

                fputcsv($out, [
                    $s->colegio?->nombreColegio ?? '—',
                    $s->plan?->nombre ?? 'Sin plan',
                    ucfirst($s->estado),
                    $s->fechaInicio?->format('d/m/Y') ?? '—',
                    $s->fechaVencimiento?->format('d/m/Y') ?? '—',
                    $dias === null ? '—' : $dias,
                ]);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, 'suscripciones_'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return Builder<Suscripcion>
     */
    private function consultaFiltrada(Request $request)
    {
        $query = Suscripcion::with(['colegio', 'plan'])->orderBy('fechaVencimiento');

        if ($request->filled('q')) {
            $buscar = $request->string('q');
            $query->whereHas('colegio', fn ($c) => $c->where('nombreColegio', 'like', "%{$buscar}%"));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('plan')) {
            $query->where('idPlan', $request->integer('plan'));
        }

        if ($request->filled('vencimiento')) {
            $dias = max(1, $request->integer('vencimiento'));
            $query->whereIn('estado', Suscripcion::ESTADOS_VIGENTES)
                ->whereBetween('fechaVencimiento', [today(), today()->addDays($dias)]);
        }

        return $query;
    }

    /**
     * Contadores para los stat-cards de arriba — una sola query agregada,
     * mismo criterio que AdminDashboardMetrics (SUM condicional en vez de
     * N queries sueltas).
     *
     * @return array{activas: int, trial: int, vencidas: int, vencenPronto: int}
     */
    private function resumen(): array
    {
        $hoy = today();
        $limit = $hoy->copy()->addDays(self::DIAS_VENCE_PRONTO);

        $fila = Suscripcion::query()->selectRaw(
            "SUM(estado = 'activa') as activas,
             SUM(estado = 'trial') as trial,
             SUM(estado = 'vencida') as vencidas,
             SUM(estado IN ('activa','trial') AND fechaVencimiento BETWEEN ? AND ?) as vencenPronto",
            [$hoy, $limit],
        )->first();

        return [
            'activas' => (int) $fila->activas,
            'trial' => (int) $fila->trial,
            'vencidas' => (int) $fila->vencidas,
            'vencenPronto' => (int) $fila->vencenPronto,
        ];
    }

    public function cambiarPlan(CambiarPlanColegioRequest $request, int $idColegio): RedirectResponse
    {
        $validated = $request->validated();

        $colegio = Colegio::findOrFail($idColegio);
        $plan = Plan::findOrFail($validated['idPlan']);

        $this->suscripciones->cambiarPlan($colegio, $plan);

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'cambiar_plan',
            'suscripcion',
            $colegio->idColegio,
            "Plan de \"{$colegio->nombreColegio}\" cambiado a \"{$plan->nombre}\".",
        );

        return back()->with('success', "Plan de {$colegio->nombreColegio} cambiado a \"{$plan->nombre}\".");
    }

    public function extender(ExtenderSuscripcionRequest $request, int $idColegio): RedirectResponse
    {
        $validated = $request->validated();

        $colegio = Colegio::findOrFail($idColegio);

        try {
            $this->suscripciones->extender($colegio, (int) $validated['dias']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'extender',
            'suscripcion',
            $colegio->idColegio,
            "Suscripción de \"{$colegio->nombreColegio}\" extendida {$validated['dias']} días.",
        );

        return back()->with('success', "Suscripción de {$colegio->nombreColegio} extendida {$validated['dias']} días.");
    }

    public function cancelar(int $idColegio): RedirectResponse
    {
        $colegio = Colegio::findOrFail($idColegio);

        try {
            $suscripcion = $this->suscripciones->cancelar($colegio);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fecha = $suscripcion->fechaVencimiento?->format('d/m/Y') ?? '—';

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'cancelar',
            'suscripcion',
            $colegio->idColegio,
            "Cancelación programada para \"{$colegio->nombreColegio}\" — conserva acceso hasta el {$fecha}.",
        );

        return back()->with('success', "Suscripción de {$colegio->nombreColegio} marcada para no renovar. Conserva acceso hasta el {$fecha}.");
    }
}
