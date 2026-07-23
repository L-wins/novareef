<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate-limiting de intentos de login/verificación reusado por LoginController
 * (usuarios), AdminLoginController (admin + 2FA) y RecuperarContrasenaController
 * — los tres reimplementaban la misma secuencia tooManyAttempts/hit/clear
 * (auditoría de duplicación, julio 2026). Deliberadamente NO arma el mensaje
 * de error ni la clave: cada caller construye su propia clave (varía: IP+
 * identificador, IP+email, IP+adminId) y su propio texto (los tres tenían
 * redacción distinta — minutos vs segundos, con/sin aviso de "intentos
 * restantes"); forzar un formato único aquí habría cambiado copy visible al
 * usuario sin necesidad.
 */
final class LoginThrottleService
{
    public function bloqueado(string $key, int $maxIntentos): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxIntentos);
    }

    /** Segundos restantes hasta que la clave bloqueada vuelva a permitir intentos. */
    public function segundosRestantes(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /** Registra un intento fallido — el bloqueo se activa al llegar a maxIntentos (ver bloqueado()). */
    public function registrarFallo(string $key, int $bloqueoSegundos): void
    {
        RateLimiter::hit($key, $bloqueoSegundos);
    }

    /** Limpia el contador tras un intento exitoso. */
    public function limpiar(string $key): void
    {
        RateLimiter::clear($key);
    }

    /** Intentos restantes antes del bloqueo — para avisos tipo "te quedan N intentos". */
    public function intentosRestantes(string $key, int $maxIntentos): int
    {
        return RateLimiter::remaining($key, $maxIntentos);
    }
}
