<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferenciaController extends Controller
{
    /**
     * Guarda la preferencia de tema del usuario autenticado.
     * El tema ya fue aplicado en el cliente — aquí solo se persiste.
     */
    public function actualizarTema(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tema' => 'required|string|in:light,dark,system',
        ]);

        $request->user()->update(['temaPreferencia' => $validated['tema']]);

        return response()->json(['success' => true]);
    }
}
