<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreColegio;
use App\Http\Requests\Admin\UpdateColegio;
use App\Models\Colegio;
use App\Models\Plan;
use App\Services\ColegioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminColegioController extends Controller
{
    public function __construct(
        private readonly ColegioService $colegios,
    ) {}

    public function index(Request $request): View
    {
        $query = Colegio::with(['suscripcionActiva.plan'])
            ->withCount('arbitros')
            ->orderBy('nombreColegio');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('nombreColegio', 'like', "%{$search}%")
                  ->orWhere('codigoColegio', 'like', "%{$search}%");
            });
        }

        $colegios = $query->paginate(15)->withQueryString();

        return view('admin.colegios.index', compact('colegios'));
    }

    public function show(int $id): View
    {
        $colegio = Colegio::with(['suscripcionActiva.plan'])->findOrFail($id);
        $stats   = $this->colegios->estadisticasArbitros($id);

        return view('admin.colegios.show', [
            'colegio'         => $colegio,
            'admin'           => $this->colegios->adminPrincipal($colegio),
            'totalArbitros'   => $stats['total'],
            'arbitrosActivos' => $stats['activos'],
            'arbitrosProceso' => $stats['enProceso'],
        ]);
    }

    public function create(): View
    {
        $planes = Plan::where('esActivo', true)->orderBy('orden')->get();

        return view('admin.colegios.create', compact('planes'));
    }

    public function store(StoreColegio $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->colegios->registrar(
                nombreColegio:       $data['nombreColegio'],
                codigoColegio:       $data['codigoColegio'],
                emailColegio:        $data['emailColegio'],
                telefonoColegio:     $data['telefonoColegio'] ?? null,
                direccionColegio:    $data['direccionColegio'] ?? null,
                ciudadColegio:       $data['ciudadColegio'] ?? null,
                departamentoColegio: $data['departamentoColegio'] ?? null,
                paisColegio:         $data['paisColegio'],
                logoColegio:         $data['logoColegio'] ?? null,
                idPlan:              (int) $data['idPlan'],
                nombreAdmin:         $data['nombreAdmin'],
                emailAdmin:          $data['emailAdmin'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['emailAdmin' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.colegios.index')
            ->with('success', 'Colegio registrado correctamente. Se enviaron las credenciales al correo del administrador.');
    }

    public function edit(int $id): View
    {
        $colegio = Colegio::with(['suscripcionActiva.plan'])->findOrFail($id);

        return view('admin.colegios.edit', compact('colegio'));
    }

    public function update(UpdateColegio $request, int $id): RedirectResponse
    {
        $colegio = Colegio::findOrFail($id);

        $colegio->update($request->validated());

        return redirect()
            ->route('admin.colegios.show', $id)
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        $colegio = Colegio::findOrFail($id);
        $label   = $this->colegios->cambiarEstado($colegio, $request->input('estado'));

        return back()->with('success', "Colegio {$label} correctamente.");
    }
}
