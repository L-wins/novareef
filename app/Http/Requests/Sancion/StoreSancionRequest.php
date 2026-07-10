<?php

declare(strict_types=1);

namespace App\Http\Requests\Sancion;

use Illuminate\Foundation\Http\FormRequest;

class StoreSancionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idArbitro'          => ['required', 'integer', 'exists:arbitros,idArbitro'],
            'idTipoSancion'      => ['required', 'integer', 'exists:tipos_sancion,idTipoSancion'],
            'idPartido'          => ['nullable', 'integer', 'exists:partidos,idPartido'],
            'motivoSancion'      => ['required', 'string'],
            'fechaHecho'         => ['required', 'date'],
            'fechaInicioSancion' => ['required', 'date'],
            'fechaFinSancion'    => ['nullable', 'date', 'after_or_equal:fechaInicioSancion'],
            'tieneMultaEconomica' => ['sometimes', 'boolean'],
            'montoMulta'         => ['required_if:tieneMultaEconomica,1', 'nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'idArbitro.required'          => 'Selecciona un árbitro.',
            'idTipoSancion.required'      => 'Selecciona un tipo de sanción.',
            'motivoSancion.required'      => 'El motivo es obligatorio.',
            'fechaHecho.required'         => 'La fecha del hecho es obligatoria.',
            'fechaInicioSancion.required' => 'La fecha de inicio es obligatoria.',
            'fechaFinSancion.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'montoMulta.required_if'      => 'Indica el valor de la multa.',
        ];
    }
}
