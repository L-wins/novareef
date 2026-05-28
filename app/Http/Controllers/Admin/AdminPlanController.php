<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        $planes = Plan::orderBy('orden')
            ->withCount([
                'suscripciones as colegios_suscritos' => fn ($q) => $q->whereIn('estado', ['activa', 'trial']),
            ])
            ->get();

        return view('admin.planes.index', compact('planes'));
    }

    public function show(int $id): View
    {
        $plan = Plan::findOrFail($id);

        $suscripciones = $plan->suscripciones()
            ->with('colegio')
            ->orderByDesc('fechaInicio')
            ->get();

        $totalActivas   = $suscripciones->whereIn('estado', ['activa', 'trial'])->count();
        $totalTrial     = $suscripciones->where('estado', 'trial')->count();
        $totalHistorico = $suscripciones->count();

        return view('admin.planes.show', compact(
            'plan',
            'suscripciones',
            'totalActivas',
            'totalTrial',
            'totalHistorico',
        ));
    }

    public function edit(int $id): View
    {
        $plan = Plan::findOrFail($id);

        return view('admin.planes.edit', compact('plan'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'nombre'            => ['required', 'string', 'max:100'],
            'precio'            => ['required', 'numeric', 'min:0'],
            'periodicidad'      => ['required', 'in:mensual,anual'],
            'limiteArbitros'    => ['nullable', 'integer', 'min:1'],
            'limiteRoles'       => ['nullable', 'integer', 'min:1'],
            'modulos'           => ['nullable', 'array'],
            'modulos.*'         => ['string'],
            'incluyePaginaWeb'  => ['boolean'],
            'incluyeOnboarding' => ['boolean'],
            'esVisible'         => ['boolean'],
            'esActivo'          => ['boolean'],
            'orden'             => ['required', 'integer', 'min:0'],
        ], [
            'nombre.required'       => 'El nombre del plan es obligatorio.',
            'nombre.max'            => 'El nombre no puede superar 100 caracteres.',
            'precio.required'       => 'El precio es obligatorio.',
            'precio.numeric'        => 'El precio debe ser un número.',
            'precio.min'            => 'El precio no puede ser negativo.',
            'periodicidad.required' => 'La periodicidad es obligatoria.',
            'periodicidad.in'       => 'La periodicidad debe ser mensual o anual.',
            'limiteArbitros.integer'=> 'El límite de árbitros debe ser un entero.',
            'limiteArbitros.min'    => 'El límite de árbitros debe ser al menos 1.',
            'limiteRoles.integer'   => 'El límite de roles debe ser un entero.',
            'limiteRoles.min'       => 'El límite de roles debe ser al menos 1.',
            'orden.required'        => 'El orden es obligatorio.',
            'orden.integer'         => 'El orden debe ser un entero.',
        ]);

        $plan->update([
            'nombre'            => $validated['nombre'],
            'precio'            => $validated['precio'],
            'periodicidad'      => $validated['periodicidad'],
            'limiteArbitros'    => $validated['limiteArbitros'] ?? null,
            'limiteRoles'       => $validated['limiteRoles'] ?? null,
            'modulosJSON'       => $validated['modulos'] ?? [],
            'incluyePaginaWeb'  => (bool) ($validated['incluyePaginaWeb'] ?? false),
            'incluyeOnboarding' => (bool) ($validated['incluyeOnboarding'] ?? false),
            'esVisible'         => (bool) ($validated['esVisible'] ?? false),
            'esActivo'          => (bool) ($validated['esActivo'] ?? false),
            'orden'             => $validated['orden'],
        ]);

        return redirect()
            ->route('admin.planes.show', $id)
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function toggleVisible(int $id): RedirectResponse
    {
        $plan           = Plan::findOrFail($id);
        $plan->esVisible = ! $plan->esVisible;
        $plan->save();

        $estado = $plan->esVisible ? 'visible' : 'oculto';

        return back()->with('success', "Plan marcado como {$estado}.");
    }

    public function toggleActivo(int $id): RedirectResponse
    {
        $plan          = Plan::findOrFail($id);
        $plan->esActivo = ! $plan->esActivo;
        $plan->save();

        $estado = $plan->esActivo ? 'activo' : 'inactivo';

        return back()->with('success', "Plan marcado como {$estado}.");
    }
}
