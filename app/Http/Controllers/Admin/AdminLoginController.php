<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function showLogin(): RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('welcome')->with('admin_modal', true);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key         = 'admin-login:' . $request->ip() . '|' . $request->input('email');
        $maxIntentos = config('admin.max_intentos', 3);
        $bloqueo     = config('admin.bloqueo_segundos', 300);

        if (RateLimiter::tooManyAttempts($key, $maxIntentos)) {
            $segundos = RateLimiter::availableIn($key);
            return back()
                ->withErrors(['email' => "Demasiados intentos fallidos. Intenta nuevamente en {$segundos} segundos."])
                ->withInput($request->only('email'));
        }

        $admin = Admin::where('email', $request->input('email'))
            ->where('activo', true)
            ->first();

        $credencialesOk = $admin && Hash::check($request->input('password'), $admin->getAuthPassword());

        DB::table('admin_login_logs')->insert([
            'ip'         => $request->ip(),
            'email'      => $request->input('email'),
            'exitoso'    => $credencialesOk,
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if (! $credencialesOk) {
            RateLimiter::hit($key, $bloqueo);
            return back()
                ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($key);
        $admin->update(['ultimo_acceso' => now()]);

        if ($admin->two_factor_enabled) {
            session(['admin_2fa_pending' => $admin->idAdmin]);
            return redirect()->route('admin.2fa');
        }

        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }

    public function show2fa(): View|RedirectResponse
    {
        if (! session()->has('admin_2fa_pending')) {
            return redirect()->route('admin.login');
        }

        return view('admin.2fa');
    }

    public function verify2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $adminId = session('admin_2fa_pending');

        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);

        if (! $admin || ! $admin->two_factor_enabled || ! $admin->google2fa_secret) {
            session()->forget('admin_2fa_pending');
            return redirect()->route('admin.login');
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $valido    = $google2fa->verifyKey($admin->getRawOriginal('google2fa_secret') ?? '', $request->input('code'));

        DB::table('admin_login_logs')->insert([
            'ip'         => $request->ip(),
            'email'      => $admin->email,
            'exitoso'    => $valido,
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        if (! $valido) {
            return back()->withErrors(['code' => 'Código incorrecto. Verifica tu aplicación de autenticación.']);
        }

        session()->forget('admin_2fa_pending');
        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
