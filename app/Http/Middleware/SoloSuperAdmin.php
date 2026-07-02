<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SoloSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || Auth::user()->rolUsuario !== 'superadmin') {
            abort(403, 'No tienes permiso para acceder aquí.');
        }

        return $next($request);
    }
}
