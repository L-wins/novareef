<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use App\Services\LimiteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCuentaAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idUsuario = (int) $this->route('id');

        return [
            'nombreUsuario' => ['required', 'string', 'max:150'],
            'emailUsuario'  => ['nullable', 'email', 'max:255', Rule::unique('usuarios', 'emailUsuario')->ignore($idUsuario, 'idUsuario')],
            'rolUsuario'    => ['required', 'in:' . implode(',', LimiteService::ROLES_ADMIN)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreUsuario.required' => 'El nombre es obligatorio.',
            'emailUsuario.email'     => 'Ingresa un correo electrónico válido.',
            'emailUsuario.unique'    => 'Ese correo ya está registrado.',
            'rolUsuario.required'    => 'Selecciona un rol.',
            'rolUsuario.in'          => 'El rol seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombreUsuario' => 'nombre',
            'emailUsuario'  => 'correo electrónico',
            'rolUsuario'    => 'rol',
        ];
    }
}
