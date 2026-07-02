<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambioContrasenaRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CambioContrasenaController extends Controller
{
    public function show(): View|RedirectResponse
    {
        // El middleware VerificarCambioContrasena redirige a esta vista cuando
        // must_change_password = true. Esta guarda cubre el caso inverso: un usuario
        // que ya cambió su contraseña navega directamente a la URL.
        if (! Auth::user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.cambiar-contrasena');
    }

    public function update(CambioContrasenaRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'passwordUsuario'      => $request->validated('nueva_password'),
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Contraseña actualizada correctamente. ¡Bienvenido a NovaReef!');
    }
}
