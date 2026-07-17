<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\Verificar2FALoginRequest;
use App\Models\Admin;
use App\Services\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
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

    public function verify2fa(Verificar2FALoginRequest $request): RedirectResponse
    {
        $adminId = $request->session()->get('admin_2fa_pending');

        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $throttleKey = $this->throttleKey2fa($request, $adminId);

        // Defensa en profundidad: el throttle genérico de escritura (30/60s) ya
        // limita el ritmo, pero un TOTP de 6 dígitos merece su propio límite en
        // vez de depender de uno compartido con el resto de rutas admin.
        if (RateLimiter::tooManyAttempts($throttleKey, config('admin.max_intentos'))) {
            $segundos = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => "Demasiados intentos fallidos. Intenta nuevamente en {$segundos} segundos."]);
        }

        $admin = Admin::find($adminId);

        if (! $admin || ! $admin->two_factor_enabled || ! $admin->google2fa_secret) {
            $request->session()->forget('admin_2fa_pending');
            return redirect()->route('admin.login');
        }

        $valido = $this->twoFactor->verificarCodigo($admin, $request->input('code'));

        $this->registrarLog($request, $admin->email, $valido);

        if (! $valido) {
            RateLimiter::hit($throttleKey, config('admin.bloqueo_segundos'));
            return back()->withErrors(['code' => 'Código incorrecto. Verifica tu aplicación de autenticación.']);
        }

        RateLimiter::clear($throttleKey);
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

    // ── Helpers privados ──────────────────

    /**
     * Clave única de throttle por IP + email — identifica al atacante, no al admin.
     */
    private function throttleKey(Request $request): string
    {
        return 'admin-login:' . $request->ip() . '|' . $request->input('email');
    }

    /**
     * Clave única por IP + admin pendiente de verificar — el 2FA no viaja con
     * email en el payload, así que la identidad la da la sesión, no el input.
     */
    private function throttleKey2fa(Request $request, int $adminId): string
    {
        return 'admin-2fa:' . $request->ip() . '|' . $adminId;
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
