<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

class StoreArbitroRequest extends ArbitroRequest
{
    public function rules(): array
    {
        return array_merge($this->reglasComunes(), [
            'emailUsuario'    => ['required', 'email', 'max:255', 'unique:usuarios,emailUsuario'],
            'passwordUsuario' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
