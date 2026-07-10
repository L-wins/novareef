<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use App\Services\LimiteService;
use Illuminate\Foundation\Http\FormRequest;

class StoreCuentaAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización (permission:gestionar-cuentas-admin) la cubre el middleware de la ruta.
        return true;
    }

    public function rules(): array
    {
        return [
            'nombreUsuario'   => ['required', 'string', 'max:150'],
            'usernameUsuario' => ['required', 'string', 'alpha_dash', 'max:60', 'unique:usuarios,usernameUsuario'],
            'emailUsuario'    => ['nullable', 'email', 'max:255', 'unique:usuarios,emailUsuario'],
            'rolUsuario'      => ['required', 'in:' . implode(',', LimiteService::ROLES_ADMIN)],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreUsuario.required'   => 'El nombre es obligatorio.',
            'usernameUsuario.required' => 'El nombre de usuario es obligatorio.',
            'usernameUsuario.alpha_dash' => 'El nombre de usuario solo puede tener letras, números, guiones y guiones bajos.',
            'usernameUsuario.unique'   => 'Ese nombre de usuario ya está en uso.',
            'emailUsuario.email'       => 'Ingresa un correo electrónico válido.',
            'emailUsuario.unique'      => 'Ese correo ya está registrado.',
            'rolUsuario.required'      => 'Selecciona un rol.',
            'rolUsuario.in'            => 'El rol seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombreUsuario'   => 'nombre',
            'usernameUsuario' => 'nombre de usuario',
            'emailUsuario'    => 'correo electrónico',
            'rolUsuario'      => 'rol',
        ];
    }
}
