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
            'motivo'    => ['nullable', 'string', 'max:2000', 'required_if:accion,anular', 'required_if:accion,apelar'],
            'resultado' => ['nullable', Rule::in(['confirmada', 'revocada']), 'required_if:accion,resolver'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required_if'    => 'Debes indicar el motivo para continuar.',
            'resultado.required_if' => 'Indica el resultado de la apelación.',
        ];
    }
}
