<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;

class ArchivarArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debes indicar el motivo del archivado.',
            'motivo.max'      => 'El motivo no puede superar los 150 caracteres.',
        ];
    }
}
