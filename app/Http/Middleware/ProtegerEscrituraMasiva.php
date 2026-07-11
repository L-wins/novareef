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
 * Algunas pantallas (ej. marcar asistencia académica uno por uno, o por
 * scanner, sobre decenas de árbitros en pocos minutos) son legítimamente
 * más "escritura-intensivas" que un formulario normal — para esas rutas
 * puntuales se permite un techo más alto vía LIMITES_PERSONALIZADOS, sin
 * debilitar el límite por defecto para el resto de la aplicación.
 *
 * Cada bloqueo queda registrado en el log para auditoría del sistema.
 */
class ProtegerEscrituraMasiva
{
    /** Escrituras permitidas por ventana (rutas sin override). */
    private const MAX_ESCRITURAS = 30;

    /** Tamaño de la ventana en segundos (rutas sin override). */
    private const VENTANA_SEGUNDOS = 60;

    /**
     * Rutas (por nombre) con techo propio: [máximo, ventana en segundos].
     * Pensado para pantallas de marcado masivo humano, no para relajar la
     * protección general contra bots.
     */
    private const LIMITES_PERSONALIZADOS = [
        // Pensado para el colegio más grande (~300 árbitros) marcando una
        // sesión completa en un solo pase, más margen para correcciones y
        // deshacer dentro del mismo minuto.
        'academico.asistencias.corregir' => [500, 60],
        'academico.scanner'              => [500, 60],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $nombreRuta = $request->route()?->getName();
        [$maxEscrituras, $ventanaSegundos] = self::LIMITES_PERSONALIZADOS[$nombreRuta]
            ?? [self::MAX_ESCRITURAS, self::VENTANA_SEGUNDOS];

        $clave = 'escrituras:' . (Auth::id() !== null ? 'user-' . Auth::id() : 'ip-' . $request->ip());

        if (RateLimiter::tooManyAttempts($clave, $maxEscrituras)) {
            $segundos = RateLimiter::availableIn($clave);

            Log::warning('Escritura masiva bloqueada', [
                'usuario' => Auth::id(),
                'ip'      => $request->ip(),
                'ruta'    => $request->path(),
                'metodo'  => $request->method(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Demasiadas operaciones en poco tiempo. Espera {$segundos} segundos e intenta de nuevo.",
                    'mensaje' => "Demasiadas operaciones en poco tiempo. Espera {$segundos} segundos e intenta de nuevo.",
                ], 429);
            }

            return back()->with('error', "Demasiadas operaciones en poco tiempo. Por seguridad, espera {$segundos} segundos e intenta de nuevo.");
        }

        RateLimiter::hit($clave, $ventanaSegundos);

        return $next($request);
    }
}
