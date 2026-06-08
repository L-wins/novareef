<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use App\Models\DisponibilidadArbitro;
use Illuminate\Foundation\Http\FormRequest;

class IndisponibilidadExtraordinariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $franjas = implode(',', array_keys(DisponibilidadArbitro::getFranjas()));

        return [
            'fechaAfectada'  => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'franjaAfectada' => ['required', "in:{$franjas}"],
            'motivo'         => ['required', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'fechaAfectada.required'       => 'La fecha es obligatoria.',
            'fechaAfectada.date_format'    => 'Formato de fecha inválido.',
            'fechaAfectada.after_or_equal' => 'No puedes reportar indisponibilidad para fechas pasadas.',
            'franjaAfectada.required'      => 'Debes seleccionar la franja horaria afectada.',
            'franjaAfectada.in'            => 'Franja horaria no válida.',
            'motivo.required'              => 'El motivo es obligatorio.',
            'motivo.max'                   => 'El motivo no puede superar los 300 caracteres.',
        ];
    }
}
