<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreColegio extends ColegioRequest
{
    public function rules(): array
    {
        return array_merge($this->reglasComunes(), [
            // En prueba gratuita el colegio no elige plan comercial — lo
            // asigna ColegioService automáticamente (ver planParaPrueba()).
            'idPlan' => ['required_unless:iniciarComoTrial,1', 'nullable', 'integer', Rule::exists('planes', 'idPlan')],
            'nombreAdmin' => ['required', 'string', 'max:150'],
            'emailAdmin' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'emailUsuario')],
            'iniciarComoTrial' => ['nullable', 'boolean'],
        ]);
    }
}
