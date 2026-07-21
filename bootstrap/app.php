<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\BlockResendWebhook;
use App\Http\Middleware\ExigirAceptacionPolitica;
use App\Http\Middleware\ProtegerEscrituraMasiva;
use App\Http\Middleware\TerminarImpersonacionExpirada;
use App\Http\Middleware\VerificarCambioContrasena;
use App\Http\Middleware\VerificarEstadoColegio;
use App\Http\Middleware\VerificarModuloPlan;
use App\Http\Middleware\VerificarPerfilCompleto;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth'          => AdminAuth::class,
            'verificar.colegio'   => VerificarEstadoColegio::class,
            'verificar.perfil'    => VerificarPerfilCompleto::class,
            'modulo'              => VerificarModuloPlan::class,
            'permission'          => PermissionMiddleware::class,
            'role'                => RoleMiddleware::class,
            'role_or_permission'  => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(prepend: [
            BlockResendWebhook::class,
        ]);
        $middleware->web(append: [
            TerminarImpersonacionExpirada::class,
            VerificarCambioContrasena::class,
            ExigirAceptacionPolitica::class,
            ProtegerEscrituraMasiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sesión/CSRF vencida (419): en vez de la página de error cruda,
        // redirige al login correspondiente (admin o usuario según la URL)
        // con un mensaje amable. Sin esto, un logout con token vencido
        // devolvía un 419 desnudo y, si el usuario volvía atrás, el
        // navegador reenviaba el mismo formulario vencido en bucle.
        //
        // Excluye peticiones que esperan JSON (fetch/axios del scanner de
        // académico, etc.) — esas ya manejan el 419 explícitamente en JS
        // (ver leerRespuestaJson en academico.js) y necesitan seguir
        // recibiendo el status 419 real, no un redirect que fetch seguiría
        // silenciosamente y confundiría con un 200 de HTML.
        //
        // Laravel convierte TokenMismatchException en HttpException(419) en
        // Handler::prepareException() ANTES de pasarla por los renderables
        // registrados aquí — por eso el tipo del callback debe ser la
        // interfaz de excepción HTTP con chequeo manual del status code, no
        // TokenMismatchException directamente (nunca haría match).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            $esAdmin = $request->is(config('admin.prefix', 'novareef-panel').'*');
            $login = $esAdmin ? route('admin.login') : route('login');

            return redirect()->guest($login)
                ->with('error', 'Tu sesión expiró por inactividad. Ingresa de nuevo para continuar.');
        });

        // Sin esto, sentry/sentry-laravel queda instalado pero inerte: ninguna
        // excepción (de request ni de job en cola) llega a Sentry, solo al log
        // local. Requisito documentado por el propio paquete para Laravel 11.
        Integration::handles($exceptions);
    })->create();
