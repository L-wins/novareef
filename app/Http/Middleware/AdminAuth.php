<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check()) {
            return redirect()->route('admin.login');
        }

        // Revalidar en cada request: si al admin lo desactivan a mitad de
        // sesión, no debe seguir operando hasta que la sesión expire sola.
        if (! $guard->user()->activo) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'Tu cuenta ha sido desactivada.');
        }

        return $next($request);
    }
}
