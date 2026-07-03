<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LimiteService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarModuloPlan
{
    public function __construct(
        private readonly LimiteService $limites,
    ) {}

    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        if (! Auth::guard('web')->check()) {
            return $next($request);
        }

        $idColegio = Auth::user()->idColegio;

        if ($idColegio === null) {
            return $next($request);
        }

        if (! $this->limites->moduloHabilitado((int) $idColegio, $modulo)) {
            return response()->view('errors.modulo-no-incluido', ['modulo' => $modulo], 403);
        }

        return $next($request);
    }
}
