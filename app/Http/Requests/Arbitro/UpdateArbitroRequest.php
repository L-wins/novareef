<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use App\Models\Arbitro;
use Illuminate\Validation\Rule;

class UpdateArbitroRequest extends ArbitroRequest
{
    public function rules(): array
    {
        $arbitro = Arbitro::findOrFail($this->route('id'));

        return array_merge($this->reglasComunes(), [
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
