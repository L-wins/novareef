<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        // privacidad.aceptar* exento por la misma razón que password.change* está
        // exento en ExigirAceptacionPolitica: evita el rebote infinito entre ambas
        // páginas cuando un usuario nuevo tiene los dos requisitos pendientes a la vez.
        if (
            Auth::guard('web')->check()
            && Auth::user()->must_change_password
            && ! $request->routeIs(
                'password.change', 'password.change.update', 'logout',
                'privacidad.politica', 'privacidad.aceptar', 'privacidad.aceptar.guardar',
            )
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
