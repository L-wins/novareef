<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RestablecerContrasenaRequest;
use App\Http\Requests\Auth\SolicitarRecuperacionRequest;
use App\Mail\RecuperarContrasenaMail;
use App\Models\User;
use App\Services\LoginThrottleService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecuperarContrasenaController extends Controller
{
    /** Solicitudes de enlace permitidas antes del bloqueo temporal. */
    private const MAX_INTENTOS = 3;

    /** Duración del bloqueo en segundos una vez agotadas las solicitudes. */
    private const BLOQUEO_SEGUNDOS = 300;

    /** Mensaje único de éxito — nunca revela si el correo existe o no. */
    private const MENSAJE_GENERICO = 'Si el correo ingresado está registrado, te enviamos un enlace para restablecer tu contraseña. Revisa también tu carpeta de spam.';

    public function __construct(private readonly LoginThrottleService $throttle) {}

    public function mostrarSolicitud(): View
    {
        return view('auth.recuperar-contrasena');
    }

    public function enviarEnlace(SolicitarRecuperacionRequest $request): RedirectResponse
    {
        $email       = mb_strtolower(trim($request->validated('email')));
        $throttleKey = $this->throttleKey($request, $email);

        if ($this->throttle->bloqueado($throttleKey, self::MAX_INTENTOS)) {
            $minutos = (int) ceil($this->throttle->segundosRestantes($throttleKey) / 60);

            return back()
                ->withErrors(['email' => "Demasiadas solicitudes. Intenta nuevamente en {$minutos} " . ($minutos === 1 ? 'minuto' : 'minutos') . '.'])
                ->withInput($request->only('email'));
        }

        // Se cuenta la solicitud exista o no la cuenta — evita usar este
        // endpoint para bombardear de correos a un usuario real, y evita
        // que el conteo mismo filtre si la cuenta existe.
        $this->throttle->registrarFallo($throttleKey, self::BLOQUEO_SEGUNDOS);

        Password::broker('users')->sendResetLink(
            ['emailUsuario' => $email],
            function (User $user, string $token): void {
                $this->enviarCorreoRecuperacion($user, $token);
            },
        );

        return back()->with('status', self::MENSAJE_GENERICO);
    }

    public function mostrarFormulario(string $token, Request $request): View
    {
        return view('auth.restablecer-contrasena', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function restablecer(RestablecerContrasenaRequest $request): RedirectResponse
    {
        $status = Password::broker('users')->reset(
            [
                'emailUsuario' => mb_strtolower(trim($request->validated('email'))),
                'password'     => $request->validated('password'),
                'token'        => $request->validated('token'),
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'passwordUsuario' => $password,
                    'remember_token'  => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Tu contraseña fue restablecida correctamente. Ya puedes iniciar sesión.');
        }

        // INVALID_USER / INVALID_TOKEN / RESET_THROTTLED → mismo mensaje,
        // no se distingue la causa exacta frente al usuario.
        return back()
            ->withErrors(['email' => 'El enlace de restablecimiento no es válido o ya expiró. Solicita uno nuevo.'])
            ->withInput($request->only('email'));
    }

    // ── Helpers privados ──────────────────

    private function throttleKey(Request $request, string $email): string
    {
        return 'password-email:' . $request->ip() . '|' . $email;
    }

    private function enviarCorreoRecuperacion(User $user, string $token): void
    {
        $url = route('password.reset', ['token' => $token, 'email' => $user->emailUsuario]);

        try {
            Mail::to($user->emailUsuario)->send(new RecuperarContrasenaMail(
                nombreUsuario:       $user->nombreUsuario,
                urlRestablecimiento: $url,
                minutosExpiracion:   (int) config('auth.passwords.users.expire'),
            ));
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar correo de recuperación de contraseña', [
                'idUsuario' => $user->idUsuario,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
