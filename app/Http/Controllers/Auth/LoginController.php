<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Intentos fallidos permitidos antes del bloqueo temporal. */
    private const MAX_INTENTOS = 3;

    /** Duración del bloqueo en segundos una vez agotados los intentos. */
    private const BLOQUEO_SEGUNDOS = 300;

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return back()
                ->withErrors(['identificador' => "Por seguridad, el acceso quedó bloqueado tras {$this->maxIntentos()} intentos fallidos. Intenta nuevamente en {$minutos} " . ($minutos === 1 ? 'minuto' : 'minutos') . '.'])
                ->withInput($request->only('identificador', 'remember'));
        }

        $credenciales = $request->credenciales();
        $remember     = $request->boolean('remember');

        if (Auth::attempt($credenciales, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Cuenta revocada con contraseña correcta: mensaje claro y honesto.
        // Solo se revela el estado si la contraseña es válida — un tercero
        // adivinando credenciales sigue recibiendo el mensaje genérico.
        if ($this->esCuentaRevocadaConPasswordValida($credenciales)) {
            return back()
                ->withErrors(['identificador' => 'Tu cuenta fue desactivada por el administrador de tu colegio. Si crees que se trata de un error, contáctalo para restablecer tu acceso.'])
                ->withInput($request->only('identificador'));
        }

        RateLimiter::hit($throttleKey, self::BLOQUEO_SEGUNDOS);

        $restantes = RateLimiter::remaining($throttleKey, self::MAX_INTENTOS);
        $aviso     = $restantes > 0
            ? ' Te ' . ($restantes === 1 ? 'queda 1 intento' : "quedan {$restantes} intentos") . ' antes del bloqueo temporal.'
            : '';

        return back()
            ->withErrors(['identificador' => 'Las credenciales ingresadas no son válidas.' . $aviso])
            ->withInput($request->only('identificador', 'remember'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    // ── Helpers privados ──────────────────

    /**
     * Clave de throttle por IP + identificador: acota al atacante sin
     * bloquear al usuario legítimo que entra desde otra red.
     */
    private function throttleKey(Request $request): string
    {
        return 'login:' . $request->ip() . '|' . mb_strtolower(trim((string) $request->input('identificador')));
    }

    private function maxIntentos(): int
    {
        return self::MAX_INTENTOS;
    }

    /**
     * Auth::attempt falla para cuentas revocadas porque CustomUserProvider
     * las filtra en retrieveByCredentials. Aquí se distingue ese caso del
     * password incorrecto para poder dar un mensaje veraz.
     *
     * @param  array<string, string>  $credenciales
     */
    private function esCuentaRevocadaConPasswordValida(array $credenciales): bool
    {
        $campo = array_key_exists('emailUsuario', $credenciales) ? 'emailUsuario' : 'usernameUsuario';

        $usuario = User::where($campo, $credenciales[$campo])->first();

        return $usuario !== null
            && $usuario->estadoUsuario !== 'activo'
            && Hash::check($credenciales['passwordUsuario'], $usuario->getAuthPassword());
    }
}
