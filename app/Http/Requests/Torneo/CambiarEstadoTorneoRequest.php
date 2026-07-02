<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoTorneoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estadoNuevo' => ['required', 'in:proximo,activo,finalizado,cancelado'],
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
