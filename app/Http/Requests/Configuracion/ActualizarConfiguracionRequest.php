<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dia_disponibilidad' => ['required', 'integer', 'min:1', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'dia_disponibilidad.required' => 'Debes seleccionar un día de la semana.',
            'dia_disponibilidad.integer'  => 'El valor del día debe ser un número entero.',
            'dia_disponibilidad.min'      => 'El día debe estar entre 1 (Lunes) y 7 (Domingo).',
            'dia_disponibilidad.max'      => 'El día debe estar entre 1 (Lunes) y 7 (Domingo).',
        ];
    }
}
