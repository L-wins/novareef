<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CambioContrasenaController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! Auth::user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.cambiar-contrasena');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'nueva_password'              => ['required', 'string', 'min:8', 'confirmed'],
                'nueva_password_confirmation' => ['required'],
            ],
            [
                'nueva_password.required'  => 'La nueva contraseña es obligatoria.',
                'nueva_password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'nueva_password.confirmed' => 'Las contraseñas no coinciden.',
                'nueva_password_confirmation.required' => 'Debes confirmar la nueva contraseña.',
            ]
        );

        $user = Auth::user();
        $user->passwordUsuario      = $request->input('nueva_password');
        $user->must_change_password = false;
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Contraseña actualizada correctamente. ¡Bienvenido a NovaReef!');
    }
}
