<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use App\Models\Arbitro;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateArbitroRequest extends ArbitroRequest
{
    public function rules(): array
    {
        // Igual que ArbitroController::arbitroDelColegio(): el scope va en la
        // query, no confiar solo en que el controlador filtre después — un
        // findOrFail() sin scope aquí dejaría que esta regla se construya con
        // la categoría de un árbitro de OTRO colegio si alguien manipula el
        // id de la ruta (el controller igual bloquea el update con 404, pero
        // no hay razón para que esta clase resuelva un modelo ajeno al tenant).
        $arbitro = Arbitro::where('idColegio', (int) Auth::user()->idColegio)
            ->findOrFail($this->route('id'));

        return array_merge($this->reglasComunes((int) $arbitro->idCategoria), [
            'emailUsuario' => [
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'emailUsuario')->ignore($arbitro->idUsuario, 'idUsuario'),
            ],
            // En actualización la contraseña es opcional: si se deja vacía, no cambia.
            'passwordUsuario' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
