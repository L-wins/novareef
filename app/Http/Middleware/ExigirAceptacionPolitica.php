<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PoliticaPrivacidadService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExigirAceptacionPolitica
{
    public function __construct(private readonly PoliticaPrivacidadService $politica) {}

    public function handle(Request $request, Closure $next): Response
    {
        // password.change* exento: si además debe cambiar la contraseña, dejamos que
        // termine ese paso primero — de lo contrario esta ruta y VerificarCambioContrasena
        // se rebotan una a la otra en un loop infinito de redirects.
        if (
            Auth::guard('web')->check()
            && $this->politica->debeAceptarGeneral(Auth::user())
            && ! $request->routeIs(
                'privacidad.politica', 'privacidad.aceptar', 'privacidad.aceptar.guardar', 'logout',
                'password.change', 'password.change.update',
            )
        ) {
            return redirect()->route('privacidad.aceptar');
        }

        return $next($request);
    }
}
