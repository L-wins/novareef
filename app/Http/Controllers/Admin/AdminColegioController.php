<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreColegio;
use App\Http\Requests\Admin\UpdateColegio;
use App\Models\Arbitro;
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

        $admin = $colegio->usuarios()
            ->where('rolUsuario', 'ejecutivo')
            ->orderByDesc('created_at')
            ->first();

        // Una sola query con conteos condicionales en lugar de 3 queries separadas.
        $stats = Arbitro::where('idColegio', $id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(estadoArbitro = 'activo') as activos")
            ->selectRaw("SUM(estadoArbitro = 'proceso_ingreso') as en_proceso")
            ->first();

        return view('admin.colegios.show', [
            'colegio'         => $colegio,
            'admin'           => $admin,
            'totalArbitros'   => (int) $stats->total,
            'arbitrosActivos' => (int) $stats->activos,
            'arbitrosProceso' => (int) $stats->en_proceso,
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
        $colegio     = Colegio::findOrFail($id);
        $nuevoEstado = $request->input('estado');

        $estadoFinal = in_array($nuevoEstado, ['activo', 'suspendido', 'inactivo'], true)
            ? $nuevoEstado
            : ($colegio->estadoColegio === 'activo' ? 'suspendido' : 'activo');

        $colegio->update(['estadoColegio' => $estadoFinal]);

        $labels = ['activo' => 'activado', 'suspendido' => 'suspendido', 'inactivo' => 'marcado como inactivo'];

        return back()->with('success', "Colegio {$labels[$estadoFinal]} correctamente.");
    }
}
