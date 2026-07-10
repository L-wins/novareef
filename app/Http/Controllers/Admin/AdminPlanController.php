<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlan;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planes,
    ) {}

    public function index(): View
    {
        $planes = Plan::orderBy('orden')
            ->withCount([
                'suscripciones as colegios_suscritos' => fn ($q) => $q->activas(),
            ])
            ->get();

        return view('admin.planes.index', compact('planes'));
    }

    public function show(int $id): View
    {
        // withCount hace los conteos en BD — no se carga toda la colección en memoria.
        $plan = Plan::withCount([
                'suscripciones as total_activas'   => fn ($q) => $q->activas(),
                'suscripciones as total_trial'     => fn ($q) => $q->enTrial(),
                'suscripciones as total_historico',
            ])
            ->findOrFail($id);

        // Solo se paginan las suscripciones para la tabla de detalle.
        $suscripciones = $plan->suscripciones()
            ->with('colegio:idColegio,nombreColegio,codigoColegio')
            ->orderByDesc('fechaInicio')
            ->paginate(20)
            ->withQueryString();

        return view('admin.planes.show', [
            'plan'            => $plan,
            'suscripciones'   => $suscripciones,
            'totalActivas'    => (int) $plan->total_activas,
            'totalTrial'      => (int) $plan->total_trial,
            'totalHistorico'  => (int) $plan->total_historico,
        ]);
    }

    public function edit(int $id): View
    {
        $plan = Plan::findOrFail($id);

        return view('admin.planes.edit', compact('plan'));
    }

    public function update(UpdatePlan $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        // prepareForValidation() ya renombró 'modulos' → 'modulosJSON' y casteó los booleanos.
        $plan->update($request->validated());

        return redirect()
            ->route('admin.planes.show', $id)
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function toggleVisible(int $id): RedirectResponse
    {
        return $this->toggleCampo($id, 'esVisible');
    }

    public function toggleActivo(int $id): RedirectResponse
    {
        return $this->toggleCampo($id, 'esActivo');
    }

    private function toggleCampo(int $id, string $campo): RedirectResponse
    {
        $plan  = Plan::findOrFail($id);
        $label = $this->planes->alternarCampo($plan, $campo);

        return back()->with('success', "Plan marcado como {$label}.");
    }
}
