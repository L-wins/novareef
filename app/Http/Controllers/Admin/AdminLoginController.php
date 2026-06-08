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
use PragmaRX\Google2FA\Google2FA;

class AdminLoginController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (RateLimiter::tooManyAttempts($this->throttleKey($request), config('admin.max_intentos'))) {
            $segundos = RateLimiter::availableIn($this->throttleKey($request));
            return back()
                ->withErrors(['email' => "Demasiados intentos fallidos. Intenta nuevamente en {$segundos} segundos."])
                ->withInput($request->only('email'));
        }

        $admin          = Admin::where('email', $request->input('email'))->where('activo', true)->first();
        $credencialesOk = $admin && Hash::check($request->input('password'), $admin->getAuthPassword());

        $this->registrarLog($request, $request->input('email'), $credencialesOk);

        if (! $credencialesOk) {
            RateLimiter::hit($this->throttleKey($request), config('admin.bloqueo_segundos'));
            return back()
                ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
                ->withInput($request->only('email'));
        }

        RateLimiter::clear($this->throttleKey($request));
        $admin->update(['ultimo_acceso' => now()]);

        if ($admin->two_factor_enabled) {
            $request->session()->put('admin_2fa_pending', $admin->idAdmin);
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
            'code' => ['required', 'digits:6'],
        ]);

        $adminId = $request->session()->get('admin_2fa_pending');

        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);

        if (! $admin || ! $admin->two_factor_enabled || ! $admin->google2fa_secret) {
            $request->session()->forget('admin_2fa_pending');
            return redirect()->route('admin.login');
        }

        $valido = $this->google2fa->verifyKey(
            $admin->getRawOriginal('google2fa_secret') ?? '',
            $request->input('code'),
        );

        $this->registrarLog($request, $admin->email, $valido);

        if (! $valido) {
            return back()->withErrors(['code' => 'Código incorrecto. Verifica tu aplicación de autenticación.']);
        }

        $request->session()->forget('admin_2fa_pending');
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

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Clave única de throttle por IP + email — identifica al atacante, no al admin.
     */
    private function throttleKey(Request $request): string
    {
        return 'admin-login:' . $request->ip() . '|' . $request->input('email');
    }

    /**
     * Persiste un intento de login (contraseña o 2FA) para auditoría.
     * Usa insert directo — no necesita modelo ni timestamps automáticos.
     */
    private function registrarLog(Request $request, string $email, bool $exitoso): void
    {
        DB::table('admin_login_logs')->insert([
            'ip'         => $request->ip(),
            'email'      => $email,
            'exitoso'    => $exitoso,
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
