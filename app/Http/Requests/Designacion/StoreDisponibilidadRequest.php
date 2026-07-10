<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use App\Models\DisponibilidadArbitro;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $franjas = implode(',', [
            ...array_keys(DisponibilidadArbitro::getFranjas()),
            DisponibilidadArbitro::FRANJA_NO_DISPONIBLE,
        ]);

        return [
            'disponibilidades'          => ['required', 'array', 'min:1', 'max:7'],
            'disponibilidades.*.fecha'  => ['required', 'date_format:Y-m-d'],
            'disponibilidades.*.franja' => ['nullable', "in:{$franjas}"],
        ];
    }

    public function messages(): array
    {
        return [
            'disponibilidades.required'             => 'Debes enviar la disponibilidad.',
            'disponibilidades.*.fecha.required'     => 'Cada día debe tener una fecha.',
            'disponibilidades.*.fecha.date_format'  => 'Formato de fecha inválido (esperado: YYYY-MM-DD).',
            'disponibilidades.*.franja.in'          => 'Franja horaria no válida.',
        ];
    }
}
