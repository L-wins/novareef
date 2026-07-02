<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Admin2FAController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

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

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'digits:6'],
        ], [
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.digits'   => 'El código debe tener exactamente 6 dígitos.',
        ]);

        try {
            $this->twoFactor->activar($this->adminAutenticado(), $request->string('codigo')->toString());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.2fa.config')
            ->with('success', '2FA activado correctamente. Tu cuenta ahora está protegida.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'La contraseña es obligatoria para desactivar el 2FA.',
        ]);

        try {
            $this->twoFactor->desactivar($this->adminAutenticado(), $request->string('password')->toString());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.2fa.config')
            ->with('success', '2FA desactivado correctamente.');
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function adminAutenticado(): Admin
    {
        /** @var Admin */
        return Auth::guard('admin')->user();
    }
}
