<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreColegio;
use App\Http\Requests\Admin\UpdateColegio;
use App\Models\Colegio;
use App\Models\Plan;
use App\Services\AdminAuditService;
use App\Services\ColegioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminColegioController extends Controller
{
    public function __construct(
        private readonly ColegioService $colegios,
        private readonly AdminAuditService $auditoria,
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
        $stats = $this->colegios->estadisticasArbitros($id);

        return view('admin.colegios.show', [
            'colegio' => $colegio,
            'admin' => $this->colegios->adminPrincipal($colegio),
            'totalArbitros' => $stats['total'],
            'arbitrosActivos' => $stats['activos'],
            'arbitrosProceso' => $stats['enProceso'],
            'planesDisponibles' => Plan::where('esActivo', true)->orderBy('orden')->get(['idPlan', 'nombre']),
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
            $colegio = $this->colegios->registrar(
                nombreColegio: $data['nombreColegio'],
                codigoColegio: $data['codigoColegio'],
                emailColegio: $data['emailColegio'],
                telefonoColegio: $data['telefonoColegio'] ?? null,
                direccionColegio: $data['direccionColegio'] ?? null,
                ciudadColegio: $data['ciudadColegio'] ?? null,
                departamentoColegio: $data['departamentoColegio'] ?? null,
                paisColegio: $data['paisColegio'],
                logoColegio: $data['logoColegio'] ?? null,
                idPlan: isset($data['idPlan']) ? (int) $data['idPlan'] : null,
                nombreAdmin: $data['nombreAdmin'],
                emailAdmin: $data['emailAdmin'],
                iniciarComoTrial: $request->boolean('iniciarComoTrial'),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['emailAdmin' => $e->getMessage()]);
        }

        $detalleTrial = $request->boolean('iniciarComoTrial')
            ? ' — prueba gratuita de '.ColegioService::DIAS_PRUEBA_GRATUITA.' días.'
            : '.';

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'crear',
            'colegio',
            $colegio->idColegio,
            "Colegio \"{$colegio->nombreColegio}\" registrado{$detalleTrial}",
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

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'editar',
            'colegio',
            $colegio->idColegio,
            "Colegio \"{$colegio->nombreColegio}\" editado.",
        );

        return redirect()
            ->route('admin.colegios.show', $id)
            ->with('success', 'Colegio actualizado correctamente.');
    }

    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        $colegio = Colegio::findOrFail($id);
        $label = $this->colegios->cambiarEstado($colegio, $request->input('estado'));

        $this->auditoria->registrar(
            Auth::guard('admin')->user(),
            'cambiar_estado',
            'colegio',
            $colegio->idColegio,
            "Colegio \"{$colegio->nombreColegio}\" marcado como {$label}.",
        );

        return back()->with('success', "Colegio {$label} correctamente.");
    }

    /**
     * Entra a la sesión del colegio como su cuenta ejecutivo principal —
     * herramienta de soporte para reproducir/depurar un problema. La sesión
     * de admin sigue viva en paralelo (guard distinto); no es necesario
     * volver a loguearse al salir. Único punto que sigue dejando rastro en
     * admin_action_logs — por transparencia, no por rendición de cuentas
     * entre varios admins (solo existe un superadmin).
     */
    public function impersonar(Request $request, int $id): RedirectResponse
    {
        $colegio = Colegio::findOrFail($id);
        $usuario = $this->colegios->adminPrincipal($colegio);

        if ($usuario === null) {
            return back()->with('error', 'Este colegio no tiene una cuenta ejecutivo para impersonar.');
        }

        $admin = Auth::guard('admin')->user();

        Auth::guard('web')->login($usuario);

        $request->session()->put('impersonacion.idAdmin', $admin->idAdmin);
        $request->session()->put('impersonacion.idColegio', $colegio->idColegio);
        $request->session()->put('impersonacion.expira', now()->addMinutes(45)->timestamp);

        $this->auditoria->registrar(
            $admin,
            'impersonar',
            'colegio',
            $colegio->idColegio,
            "Entró como \"{$usuario->nombreUsuario}\" ({$colegio->nombreColegio}).",
        );

        return redirect()->route('dashboard');
    }
}
