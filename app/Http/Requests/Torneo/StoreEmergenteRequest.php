<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idArbitro'      => ['required', 'exists:arbitros,idArbitro'],
            'idSede'         => ['required', 'exists:sedes_torneo,idSede'],
            'fechaEmergente' => ['required', 'date_format:Y-m-d'],
            'notas'          => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'idArbitro.required'      => 'Debes seleccionar un árbitro.',
            'idArbitro.exists'        => 'El árbitro seleccionado no es válido.',
            'idSede.required'         => 'Debes seleccionar una sede.',
            'idSede.exists'           => 'La sede seleccionada no es válida.',
            'fechaEmergente.required' => 'La fecha es obligatoria.',
            'fechaEmergente.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
            'notas.max'               => 'Las notas no pueden superar los 300 caracteres.',
        ];
    }
}
