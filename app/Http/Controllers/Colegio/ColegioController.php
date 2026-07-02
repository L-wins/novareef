<?php

declare(strict_types=1);

namespace App\Http\Controllers\Colegio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreColegio;
use App\Http\Requests\Admin\UpdateColegio;
use App\Models\Colegio;
use App\Models\Plan;
use App\Services\ColegioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ColegioController extends Controller
{
    public function __construct(
        private readonly ColegioService $colegios,
    ) {}

    public function index(): View
    {
        // Columnas mínimas que usa la vista: código, nombre, ciudad, plan, estado, acciones.
        $colegios = Colegio::select([
                'idColegio', 'nombreColegio', 'codigoColegio',
                'ciudadColegio', 'estadoColegio', 'planColegio',
            ])
            ->orderBy('nombreColegio')
            ->get();

        return view('colegios.index', compact('colegios'));
    }

    public function show(int $id): View
    {
        // show usa todas las columnas del colegio — sin select() restrictivo.
        $colegio = Colegio::with(['suscripcionActiva.plan'])->findOrFail($id);

        return view('colegios.show', compact('colegio'));
    }

    public function create(): View
    {
        $planes = Plan::where('esActivo', true)->orderBy('orden')->get();

        return view('colegios.create', compact('planes'));
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
            ->route('colegios.index')
            ->with('success', 'Colegio registrado correctamente. Se enviaron las credenciales de acceso al correo del administrador.');
    }

    public function edit(int $id): View
    {
        $colegio = Colegio::findOrFail($id);

        return view('colegios.edit', compact('colegio'));
    }

    public function update(UpdateColegio $request, int $id): RedirectResponse
    {
        Colegio::findOrFail($id)->update($request->validated());

        return redirect()
            ->route('colegios.show', $id)
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        $colegio     = Colegio::findOrFail($id);
        $estadoFinal = $colegio->estadoColegio === 'activo' ? 'suspendido' : 'activo';

        $colegio->update(['estadoColegio' => $estadoFinal]);

        $accion = $estadoFinal === 'activo' ? 'activado' : 'suspendido';

        return back()->with('success', "Colegio {$accion} correctamente.");
    }
}
