<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Arbitro;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarPerfilCompleto
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            Auth::guard('web')->check()
            && Auth::user()->rolUsuario === 'arbitro'
            && ! $request->routeIs(
                'arbitros.completar-perfil',
                'arbitros.guardar-perfil',
                'password.change',
                'password.change.update',
                'logout',
            )
        ) {
            $arbitro = Arbitro::where('idUsuario', Auth::id())->first();

            if ($arbitro && $arbitro->pesoArbitro === null) {
                return redirect()->route('arbitros.completar-perfil');
            }
        }

        return $next($request);
    }
}
