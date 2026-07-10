<?php

namespace App\Providers;

use App\Auth\CustomUserProvider;
use App\Support\PasswordGenerator;
use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        // 'dashboard' se registra aparte de 'layouts.app': su contenido (@section('contenido'))
        // se evalúa y captura ANTES de que la vista padre (layouts.app) exista, así que un
        // composer solo en 'layouts.app' nunca alcanza a inyectar la variable a tiempo ahí.
        View::composer(['layouts.app', 'dashboard'], SidebarComposer::class);
    }
}
