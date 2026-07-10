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
            'identificador'   => ['required', 'string', 'max:255'],
            'passwordUsuario' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required'   => 'El usuario o correo es obligatorio.',
            'passwordUsuario.required' => 'La contraseña es obligatoria.',
        ];
    }

    /**
     * Arma el array de credenciales para Auth::attempt(), detectando si el
     * identificador ingresado es un email (columna emailUsuario) o un
     * username (columna usernameUsuario — cuentas admin sin email propio).
     *
     * @return array<string, string>
     */
    public function credenciales(): array
    {
        $valor = trim($this->string('identificador')->toString());
        $campo = filter_var($valor, FILTER_VALIDATE_EMAIL) ? 'emailUsuario' : 'usernameUsuario';

        return [$campo => $valor, 'passwordUsuario' => $this->string('passwordUsuario')->toString()];
    }
}
