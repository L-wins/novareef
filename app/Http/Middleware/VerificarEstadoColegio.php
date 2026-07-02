<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarEstadoColegio
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (is_null($user->idColegio)) {
            return $next($request);
        }

        $colegio = $user->colegio;

        if ($colegio && in_array($colegio->estadoColegio, ['suspendido', 'inactivo'], true)) {
            return response()->view('errors.colegio-suspendido', ['colegio' => $colegio], 403);
        }

        return $next($request);
    }
}
