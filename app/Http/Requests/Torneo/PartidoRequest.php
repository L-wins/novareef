<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base compartida para store y update de partido.
 * La diferencia entre ambas es el $idTorneo para scoping del unique y
 * si se ignora o no el propio registro.
 */
abstract class PartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function reglas(int $idTorneo): array
    {
        return [
            'idDivision' => [
                'required',
                Rule::exists('divisiones_torneo', 'idDivision')->where('idTorneo', $idTorneo),
            ],
            'idSede' => [
                'nullable',
                Rule::exists('sedes_torneo', 'idSede')->where('idTorneo', $idTorneo),
            ],
            'idFormato'          => ['required', 'exists:formatos_designacion,idFormato'],
            'equipoLocal'        => ['required', 'string', 'max:150'],
            'equipoVisitante'    => ['required', 'string', 'max:150', 'different:equipoLocal'],
            'fechaPartido'       => ['required', 'date_format:Y-m-d'],
            'horaPartido'        => ['required', 'date_format:H:i'],
            'observaciones'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'idDivision.required'      => 'Debes seleccionar una división.',
            'idDivision.exists'        => 'La división seleccionada no pertenece a este torneo.',
            'idSede.exists'            => 'La sede seleccionada no pertenece a este torneo.',
            'idFormato.required'       => 'Debes seleccionar el formato de designación.',
            'idFormato.exists'         => 'El formato seleccionado no es válido.',
            'equipoLocal.required'     => 'El equipo local es obligatorio.',
            'equipoVisitante.required' => 'El equipo visitante es obligatorio.',
            'equipoVisitante.different'=> 'El visitante no puede ser igual al local.',
            'fechaPartido.required'    => 'La fecha del partido es obligatoria.',
            'fechaPartido.date_format' => 'La fecha debe tener formato YYYY-MM-DD.',
            'horaPartido.required'     => 'La hora del partido es obligatoria.',
            'horaPartido.date_format'  => 'La hora debe tener formato HH:MM.',
        ];
    }
}
