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
