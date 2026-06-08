<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;

class ToggleEstadoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estadoNuevo' => ['required', 'exists:estados_arbitro,nombre'],
            'motivo'      => ['nullable', 'string', 'max:500',
                              'required_if:estadoNuevo,suspendido',
                              'required_if:estadoNuevo,retirado'],
            'fechaInicio' => ['nullable', 'date', 'required_if:estadoNuevo,suspendido'],
            'fechaFin'    => ['nullable', 'date', 'after:fechaInicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'estadoNuevo.required'    => 'Debes seleccionar un estado.',
            'estadoNuevo.exists'      => 'El estado seleccionado no es válido.',
            'motivo.required_if'      => 'El motivo es obligatorio para este estado.',
            'fechaInicio.required_if' => 'La fecha de inicio es obligatoria para suspensiones.',
            'fechaFin.after'          => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
