<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class Admin2FAController extends Controller
{
    public function show(): View
    {
        $admin = Auth::guard('admin')->user();

        if ($admin->two_factor_enabled) {
            return view('admin.2fa-config', ['admin' => $admin, 'activo' => true]);
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        $secret = $admin->getRawOriginal('google2fa_secret');
        if (! $secret) {
            $secret = $google2fa->generateSecretKey();
            $admin->forceFill(['google2fa_secret' => $secret])->saveQuietly();
        }

        $otpUrl   = $google2fa->getQRCodeUrl('NovaReef', $admin->email, $secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $qrSvg    = (new Writer($renderer))->writeString($otpUrl);

        return view('admin.2fa-config', [
            'admin'  => $admin,
            'activo' => false,
            'secret' => $secret,
            'qrSvg'  => $qrSvg,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'digits:6'],
        ], [
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.digits'   => 'El código debe tener exactamente 6 dígitos.',
        ]);

        $admin  = Auth::guard('admin')->user();
        $secret = $admin->getRawOriginal('google2fa_secret');

        if (! $secret) {
            return back()->withErrors(['codigo' => 'No se encontró clave secreta. Recarga la página.']);
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        if (! $google2fa->verifyKey($secret, $request->input('codigo'))) {
            return back()->withErrors(['codigo' => 'Código inválido. Verifica tu aplicación de autenticación e inténtalo de nuevo.']);
        }

        $admin->two_factor_enabled = true;
        $admin->save();

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

        $admin = Auth::guard('admin')->user();

        if (! Hash::check($request->input('password'), $admin->getAuthPassword())) {
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        $admin->two_factor_enabled = false;
        $admin->google2fa_secret   = null;
        $admin->save();

        return redirect()
            ->route('admin.2fa.config')
            ->with('success', '2FA desactivado correctamente.');
    }
}
