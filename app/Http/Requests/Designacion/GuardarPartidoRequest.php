<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class GuardarPartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se centraliza en el controlador / middleware.
    }

    public function rules(): array
    {
        return [
            'idTorneo'        => ['required', 'integer'],
            'idDivision'      => ['required', 'integer'],
            'idSede'          => ['required', 'integer'],
            'idFormato'       => ['required', 'integer'],
            'equipoLocal'     => ['required', 'string', 'max:100'],
            'equipoVisitante' => ['required', 'string', 'max:100'],
            'fechaPartido'    => ['required', 'date_format:Y-m-d'],
            'horaPartido'     => ['required', 'date_format:H:i'],
            'observaciones'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
