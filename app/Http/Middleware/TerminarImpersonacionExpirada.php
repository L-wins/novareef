<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta automáticamente una sesión de impersonación (ver
 * AdminColegioController::impersonar()) 45 min después de iniciada, por si
 * el admin olvida pulsar "Salir". Solo actúa cuando hay una impersonación
 * activa en la sesión — no afecta el login normal de un usuario de colegio.
 */
class TerminarImpersonacionExpirada
{
    public function handle(Request $request, Closure $next): Response
    {
        $expira = $request->session()->get('impersonacion.expira');

        if ($expira !== null && now()->timestamp >= $expira) {
            Auth::guard('web')->logout();
            $request->session()->forget(['impersonacion.idAdmin', 'impersonacion.idColegio', 'impersonacion.expira']);
        }

        return $next($request);
    }
}
