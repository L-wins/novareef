<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emailUsuario'    => ['required', 'email'],
            'passwordUsuario' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'emailUsuario.required'    => 'El correo electrónico es obligatorio.',
            'emailUsuario.email'       => 'Ingresa un correo electrónico válido.',
            'passwordUsuario.required' => 'La contraseña es obligatoria.',
        ];
    }
}
