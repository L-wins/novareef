<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPreferenciaController extends Controller
{
    /**
     * Guarda la preferencia de tema del superadmin autenticado.
     * El tema ya fue aplicado en el cliente — aquí solo se persiste.
     */
    public function actualizarTema(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tema' => 'required|string|in:light,dark,system',
        ]);

        Auth::guard('admin')->user()->update(['temaPreferencia' => $validated['tema']]);

        return response()->json(['success' => true]);
    }
}
