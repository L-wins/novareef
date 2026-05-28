<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\BlockResendWebhook;
use App\Http\Middleware\SoloSuperAdmin;
use App\Http\Middleware\VerificarCambioContrasena;
use App\Http\Middleware\VerificarEstadoColegio;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth'        => AdminAuth::class,
            'verificar.colegio' => VerificarEstadoColegio::class,
            'solo.superadmin'   => SoloSuperAdmin::class,
        ]);
        $middleware->web(prepend: [
            BlockResendWebhook::class,
        ]);
        $middleware->web(append: [
            VerificarCambioContrasena::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
