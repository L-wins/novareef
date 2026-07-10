<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevisarJustificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accion'        => ['required', Rule::in(['aprobar', 'rechazar'])],
            'motivoRechazo' => ['nullable', 'string', 'required_if:accion,rechazar'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivoRechazo.required_if' => 'El motivo de rechazo es obligatorio.',
        ];
    }
}
