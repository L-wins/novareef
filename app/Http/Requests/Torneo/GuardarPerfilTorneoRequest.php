<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

class GuardarPerfilTorneoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reglamentoPDF' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'reglamentoPDF.mimes' => 'El reglamento debe ser un archivo PDF.',
            'reglamentoPDF.max'   => 'El reglamento no puede superar los 20 MB.',
        ];
    }
}
