<?php

namespace App\Providers;

use App\Auth\CustomUserProvider;
use App\Support\PasswordGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('eloquent-custom', function ($app, array $config) {
            return new CustomUserProvider($app['hash'], $config['model']);
        });

        Str::macro('safePassword', fn (int $length = 14) => PasswordGenerator::generate($length));
    }
}
