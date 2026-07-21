<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PoliticaPrivacidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PoliticaPrivacidadController extends Controller
{
    public function __construct(private readonly PoliticaPrivacidadService $politica) {}

    public function mostrar(): View
    {
        return view('privacidad.politica');
    }

    public function aceptar(): View
    {
        return view('privacidad.aceptar');
    }

    public function guardarAceptacion(Request $request): RedirectResponse
    {
        $request->validate(['acepto' => ['accepted']], [
            'acepto.accepted' => 'Debes aceptar la política de tratamiento de datos para continuar.',
        ]);

        $this->politica->registrarAceptacionGeneral(Auth::user(), $request->ip());

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Gracias por aceptar la política de tratamiento de datos.');
    }
}
