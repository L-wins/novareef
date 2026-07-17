<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Verificar2FALoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La sesión "admin_2fa_pending" es el chequeo real, hecho en el controlador.
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }
}
