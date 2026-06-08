<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class Admin2FAController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function show(): View
    {
        $admin = $this->adminAutenticado();

        if ($admin->two_factor_enabled) {
            return view('admin.2fa-config', ['admin' => $admin, 'activo' => true]);
        }

        $secret = $this->obtenerOCrearSecret($admin);

        return view('admin.2fa-config', [
            'admin'  => $admin,
            'activo' => false,
            'secret' => $secret,
            'qrSvg'  => $this->generarQrSvg($admin->email, $secret),
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

        $admin  = $this->adminAutenticado();
        $secret = $admin->getRawOriginal('google2fa_secret');

        if (! $secret) {
            return back()->withErrors(['codigo' => 'No se encontró clave secreta. Recarga la página.']);
        }

        if (! $this->google2fa->verifyKey($secret, $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'Código inválido. Verifica tu aplicación de autenticación e inténtalo de nuevo.']);
        }

        $admin->update(['two_factor_enabled' => true]);

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

        $admin = $this->adminAutenticado();

        if (! Hash::check($request->input('password'), $admin->getAuthPassword())) {
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        $admin->update([
            'two_factor_enabled' => false,
            'google2fa_secret'   => null,
        ]);

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

    /**
     * Devuelve el secret existente o genera uno nuevo y lo persiste.
     * saveQuietly() evita disparar eventos de modelo durante la configuración inicial.
     */
    private function obtenerOCrearSecret(Admin $admin): string
    {
        $secret = $admin->getRawOriginal('google2fa_secret');

        if ($secret) {
            return $secret;
        }

        $secret = $this->google2fa->generateSecretKey();
        $admin->forceFill(['google2fa_secret' => $secret])->saveQuietly();

        return $secret;
    }

    /**
     * Genera el SVG del QR para escanear con la app de autenticación.
     * Tamaño 220px, backend SVG (sin dependencia de extensiones de imagen PHP).
     */
    private function generarQrSvg(string $email, string $secret): string
    {
        $otpUrl   = $this->google2fa->getQRCodeUrl('NovaReef', $email, $secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpUrl);
    }
}
