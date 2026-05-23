<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CustomUserProvider extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials): bool
    {
        $passwordKey = $user->getAuthPasswordName();

        if (is_null($plain = $credentials[$passwordKey] ?? null)) {
            return false;
        }   

        if (is_null($hashed = $user->getAuthPassword())) {
            return false;
        }

        return $this->hasher->check($plain, $hashed);
    }

    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $passwordKey = $user->getAuthPasswordName();

        $user->forceFill([
            $passwordKey => $this->hasher->make($credentials[$passwordKey]),
        ])->save();
    }
}
