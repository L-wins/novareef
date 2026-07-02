<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockResendWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = trim((string) config('resend.path', 'resend'), '/');

        if (! app()->environment('production') && $request->is("{$path}/webhook")) {
            abort(404);
        }

        return $next($request);
    }
}
