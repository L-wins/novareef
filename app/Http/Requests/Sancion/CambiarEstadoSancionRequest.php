<?php

declare(strict_types=1);

namespace App\Http\Requests\Sancion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoSancionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accion'    => ['required', Rule::in(['cumplir', 'anular', 'apelar', 'resolver'])],
            'motivo'    => ['nullable', 'string', 'required_if:accion,anular'],
            'resultado' => ['nullable', Rule::in(['confirmada', 'revocada']), 'required_if:accion,resolver'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required_if'    => 'El motivo es obligatorio para anular una sanción.',
            'resultado.required_if' => 'Indica el resultado de la apelación.',
        ];
    }
}
