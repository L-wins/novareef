<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frena el ingreso masivo de datos por scripts o automatizaciones:
 * limita las peticiones de escritura (POST/PUT/PATCH/DELETE) por
 * usuario autenticado — o por IP si es un visitante. Un humano usando
 * formularios no se acerca al límite; un bot lo agota en segundos.
 *
 * Cada bloqueo queda registrado en el log para auditoría del sistema.
 */
class ProtegerEscrituraMasiva
{
    /** Escrituras permitidas por ventana. */
    private const MAX_ESCRITURAS = 30;

    /** Tamaño de la ventana en segundos. */
    private const VENTANA_SEGUNDOS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $clave = 'escrituras:' . (Auth::id() !== null ? 'user-' . Auth::id() : 'ip-' . $request->ip());

        if (RateLimiter::tooManyAttempts($clave, self::MAX_ESCRITURAS)) {
            $segundos = RateLimiter::availableIn($clave);

            Log::warning('Escritura masiva bloqueada', [
                'usuario' => Auth::id(),
                'ip'      => $request->ip(),
                'ruta'    => $request->path(),
                'metodo'  => $request->method(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'mensaje' => "Demasiadas operaciones en poco tiempo. Espera {$segundos} segundos e intenta de nuevo.",
                ], 429);
            }

            return back()->with('error', "Demasiadas operaciones en poco tiempo. Por seguridad, espera {$segundos} segundos e intenta de nuevo.");
        }

        RateLimiter::hit($clave, self::VENTANA_SEGUNDOS);

        return $next($request);
    }
}
