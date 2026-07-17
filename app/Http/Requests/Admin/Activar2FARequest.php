<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class Activar2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se centraliza en el middleware admin.auth.
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.digits'   => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
