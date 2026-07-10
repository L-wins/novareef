<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Provider personalizado para el guard 'web'.
 * Necesario porque el modelo User usa columnas no estándar (emailUsuario, passwordUsuario).
 * El padre de Laravel 11 resuelve la clave de escritura con getAuthPasswordName(), pero
 * sigue leyendo $credentials['password'] en rehashPasswordIfRequired — este override lo corrige.
 */
class CustomUserProvider extends EloquentUserProvider
{
    /**
     * Cuentas con estadoUsuario distinto de 'activo' (revocadas/suspendidas) no
     * deben poder iniciar sesión: se filtran aquí, antes de crear la sesión.
     * Ante terceros el error sigue siendo el genérico de "credenciales inválidas"
     * (no revela que la cuenta existe); solo cuando la contraseña es correcta,
     * LoginController detecta el caso y muestra el mensaje veraz de cuenta
     * desactivada.
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?UserContract
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user !== null && $user->estadoUsuario !== 'activo') {
            return null;
        }

        return $user;
    }

    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials): bool
    {
        $passwordKey = $user->getAuthPasswordName();
        $plain       = $credentials[$passwordKey] ?? null;
        $hashed      = $user->getAuthPassword();

        if (empty($plain) || empty($hashed)) {
            return false;
        }

        return $this->hasher->check($plain, $hashed);
    }

    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        $hashed = $user->getAuthPassword();

        if (! $this->hasher->needsRehash($hashed) && ! $force) {
            return;
        }

        $passwordKey = $user->getAuthPasswordName();

        $user->forceFill([
            $passwordKey => $this->hasher->make($credentials[$passwordKey]),
        ])->save();
    }
}
