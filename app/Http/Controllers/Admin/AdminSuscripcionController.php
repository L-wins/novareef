<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CambiarPlanColegioRequest;
use App\Http\Requests\Admin\ExtenderSuscripcionRequest;
use App\Models\Colegio;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Services\SuscripcionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSuscripcionController extends Controller
{
    public function __construct(private readonly SuscripcionService $suscripciones) {}

    /**
     * Listado transversal de todas las suscripciones (de todos los colegios),
     * con filtros por estado y por plan.
     */
    public function index(Request $request): View
    {
        $query = Suscripcion::with(['colegio', 'plan'])->orderByDesc('fechaVencimiento');

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('plan')) {
            $query->where('idPlan', $request->integer('plan'));
        }

        $suscripciones = $query->paginate(20)->withQueryString();
        $planes        = Plan::orderBy('orden')->get(['idPlan', 'nombre']);

        return view('admin.suscripciones.index', compact('suscripciones', 'planes'));
    }

    public function cambiarPlan(CambiarPlanColegioRequest $request, int $idColegio): RedirectResponse
    {
        $validated = $request->validated();

        $colegio = Colegio::findOrFail($idColegio);
        $plan    = Plan::findOrFail($validated['idPlan']);

        $this->suscripciones->cambiarPlan($colegio, $plan);

        return back()->with('success', "Plan de {$colegio->nombreColegio} cambiado a \"{$plan->nombre}\".");
    }

    public function extender(ExtenderSuscripcionRequest $request, int $idColegio): RedirectResponse
    {
        $validated = $request->validated();

        $colegio = Colegio::findOrFail($idColegio);

        try {
            $this->suscripciones->extender($colegio, $validated['dias']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Suscripción de {$colegio->nombreColegio} extendida {$validated['dias']} días.");
    }

    public function cancelar(int $idColegio): RedirectResponse
    {
        $colegio = Colegio::findOrFail($idColegio);

        try {
            $this->suscripciones->cancelar($colegio);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Suscripción de {$colegio->nombreColegio} cancelada.");
    }
}
