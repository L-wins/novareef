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
        if (
            Auth::guard('web')->check()
            && Auth::user()->must_change_password
            && ! $request->routeIs('password.change', 'logout')
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
