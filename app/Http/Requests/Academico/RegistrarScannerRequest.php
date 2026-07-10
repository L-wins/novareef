<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarScannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idSesion'     => ['required', 'integer', 'exists:sesiones_academicas,idSesion'],
            'codigoCarnet' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigoCarnet.required' => 'Escanea o escribe el código de carné.',
        ];
    }
}
