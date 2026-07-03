<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Concerns\ResuelveColegio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreCuentaAdminRequest;
use App\Http\Requests\Configuracion\UpdateCuentaAdminRequest;
use App\Models\User;
use App\Services\CuentaAdminService;
use App\Services\LimiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CuentaAdminController extends Controller
{
    use ResuelveColegio;

    public function __construct(
        private readonly CuentaAdminService $cuentasAdmin,
        private readonly LimiteService      $limites,
    ) {}

    public function index(): View
    {
        $idColegio = $this->idColegioActivo();

        $cuentas = User::where('idColegio', $idColegio)
            ->whereIn('rolUsuario', LimiteService::ROLES_ADMIN)
            ->orderBy('nombreUsuario')
            ->paginate(15);

        return view('configuracion.cuentas-admin.index', [
            'cuentas'    => $cuentas,
            'usadas'     => $this->limites->cuentasAdminActivas($idColegio),
            'limite'     => $this->limites->limiteCuentasAdmin($idColegio),
            'porcentaje' => $this->limites->porcentajeUsoCuentasAdmin($idColegio),
        ]);
    }

    public function create(): View
    {
        return view('configuracion.cuentas-admin.create');
    }

    public function store(StoreCuentaAdminRequest $request): RedirectResponse
    {
        $datos     = $request->validated();
        $idColegio = $this->idColegioActivo();
        $colegio   = Auth::user()->colegio;

        try {
            $this->cuentasAdmin->crear(
                idColegio:                $idColegio,
                nombreColegio:            $colegio?->nombreColegio ?? 'NovaReef',
                urlAcceso:                config('app.url') . '/login',
                nombre:                   $datos['nombreUsuario'],
                username:                 $datos['usernameUsuario'],
                email:                    $datos['emailUsuario'] ?? null,
                emailColegioNotificacion: $colegio?->emailColegio,
                rol:                      $datos['rolUsuario'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('configuracion.cuentas-admin.index')
            ->with('success', 'Cuenta admin creada correctamente. Las credenciales se enviaron al correo del colegio.');
    }

    public function edit(int $id): View
    {
        return view('configuracion.cuentas-admin.edit', [
            'cuenta' => $this->cuentaDelColegio($id),
        ]);
    }

    public function update(UpdateCuentaAdminRequest $request, int $id): RedirectResponse
    {
        $cuenta = $this->cuentaDelColegio($id);

        $this->cuentasAdmin->actualizar($cuenta, $request->validated());

        return redirect()
            ->route('configuracion.cuentas-admin.index')
            ->with('success', 'Cuenta admin actualizada correctamente.');
    }

    public function revocar(int $id): RedirectResponse
    {
        $cuenta = $this->cuentaDelColegio($id);

        $this->cuentasAdmin->revocar($cuenta);

        return back()->with('success', 'Cuenta revocada — ya no puede iniciar sesión.');
    }

    public function reactivar(int $id): RedirectResponse
    {
        $cuenta = $this->cuentaDelColegio($id);

        try {
            $this->cuentasAdmin->reactivar($cuenta);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cuenta reactivada correctamente.');
    }

    private function cuentaDelColegio(int $id): User
    {
        return User::where('idColegio', $this->idColegioActivo())
            ->whereIn('rolUsuario', LimiteService::ROLES_ADMIN)
            ->findOrFail($id);
    }
}
