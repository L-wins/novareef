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
            // Nullable: hay sanciones puramente económicas, sin ningún rango
            // de suspensión — required_with obliga a tener inicio si se puso
            // fin, pero permite inicio solo (suspensión indefinida) o
            // ninguno de los dos (solo multa, sin suspensión).
            'fechaInicioSancion' => ['nullable', 'date', 'required_with:fechaFinSancion'],
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
            'fechaInicioSancion.required_with' => 'Si defines una fecha de fin, primero debes indicar el inicio de la suspensión.',
            'fechaFinSancion.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'montoMulta.required_if'      => 'Indica el valor de la multa.',
        ];
    }
}
