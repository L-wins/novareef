<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idDivision'      => ['required', 'integer'],
            'idSede'          => ['nullable', 'integer'],
            'idFormato'       => ['required', 'integer'],
            'equipoLocal'     => ['required', 'string', 'max:150'],
            'equipoVisitante' => ['required', 'string', 'max:150', 'different:equipoLocal'],
            'fechaPartido'    => ['required', 'date_format:Y-m-d'],
            'horaPartido'     => ['required', 'date_format:H:i'],
            'observaciones'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'idDivision.required'       => 'Debes seleccionar una división.',
            'equipoLocal.required'      => 'El equipo local es obligatorio.',
            'equipoVisitante.required'  => 'El equipo visitante es obligatorio.',
            'equipoVisitante.different' => 'El visitante no puede ser igual al local.',
            'fechaPartido.required'     => 'La fecha del partido es obligatoria.',
            'fechaPartido.date_format'  => 'La fecha debe tener formato YYYY-MM-DD.',
            'horaPartido.required'      => 'La hora del partido es obligatoria.',
            'horaPartido.date_format'   => 'La hora debe tener formato HH:MM.',
        ];
    }
}
