<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activar2FARequest;
use App\Http\Requests\Admin\Desactivar2FARequest;
use App\Models\Admin;
use App\Services\AdminAuditService;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Admin2FAController extends Controller
{
    public function __construct(
        private readonly AdminTwoFactorService $twoFactor,
        private readonly AdminAuditService $auditoria,
    ) {}

    public function show(): View
    {
        $admin = $this->adminAutenticado();

        if ($admin->two_factor_enabled) {
            return view('admin.2fa-config', ['admin' => $admin, 'activo' => true]);
        }

        $secret = $this->twoFactor->obtenerOCrearSecret($admin);

        return view('admin.2fa-config', [
            'admin'  => $admin,
            'activo' => false,
            'secret' => $secret,
            'qrSvg'  => $this->twoFactor->generarQrSvg($admin->email, $secret),
        ]);
    }

    public function enable(Activar2FARequest $request): RedirectResponse
    {
        $admin = $this->adminAutenticado();

        try {
            $this->twoFactor->activar($admin, $request->string('codigo')->toString());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()]);
        }

        $this->auditoria->registrar($admin, 'activar_2fa', 'admin', $admin->idAdmin);

        return redirect()
            ->route('admin.2fa.config')
            ->with('success', '2FA activado correctamente. Tu cuenta ahora está protegida.');
    }

    public function disable(Desactivar2FARequest $request): RedirectResponse
    {
        $admin = $this->adminAutenticado();

        try {
            $this->twoFactor->desactivar($admin, $request->string('password')->toString());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        $this->auditoria->registrar($admin, 'desactivar_2fa', 'admin', $admin->idAdmin);

        return redirect()
            ->route('admin.2fa.config')
            ->with('success', '2FA desactivado correctamente.');
    }

    // ── Helpers privados ──────────────────

    private function adminAutenticado(): Admin
    {
        /** @var Admin */
        return Auth::guard('admin')->user();
    }
}
