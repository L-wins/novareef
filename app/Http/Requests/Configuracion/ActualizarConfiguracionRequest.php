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
            'dia_disponibilidad'        => ['required', 'integer', 'min:1', 'max:7'],
            'horas_limite_confirmacion' => ['required', 'integer', 'min:1', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'dia_disponibilidad.required' => 'Debes seleccionar un día de la semana.',
            'dia_disponibilidad.integer'  => 'El valor del día debe ser un número entero.',
            'dia_disponibilidad.min'      => 'El día debe estar entre 1 (Lunes) y 7 (Domingo).',
            'dia_disponibilidad.max'      => 'El día debe estar entre 1 (Lunes) y 7 (Domingo).',

            'horas_limite_confirmacion.required' => 'Debes indicar las horas límite de confirmación.',
            'horas_limite_confirmacion.integer'  => 'Las horas límite deben ser un número entero.',
            'horas_limite_confirmacion.min'      => 'El mínimo es 1 hora.',
            'horas_limite_confirmacion.max'      => 'El máximo es 72 horas.',
        ];
    }
}
