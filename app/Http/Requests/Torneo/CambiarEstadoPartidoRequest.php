<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoPartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estadoNuevo' => ['required', 'in:programado,finalizado,aplazado,cancelado'],
        ];
    }

    public function messages(): array
    {
        return [
            'estadoNuevo.required' => 'Debes seleccionar un nuevo estado.',
            'estadoNuevo.in'       => 'El estado seleccionado no es válido.',
        ];
    }
}
