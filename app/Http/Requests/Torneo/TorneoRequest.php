<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base compartida para store y update de torneo.
 * La diferencia es que modalidadPago es inmutable en update cuando ya hay partidos.
 */
abstract class TorneoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function reglasBase(): array
    {
        return [
            'nombreTorneo'        => ['required', 'string', 'max:255'],
            'tipoTorneo'          => ['required', 'in:local,zonal,oficial'],
            'organizadorNombre'   => ['required', 'string', 'max:150'],
            'organizadorTelefono' => ['nullable', 'string', 'max:20'],
            'organizadorEmail'    => ['nullable', 'email', 'max:255'],
            'temporada'           => ['required', 'integer', 'min:2020', 'max:2050'],
            'fechaInicio'         => ['required', 'date_format:Y-m-d'],
            'fechaFin'            => ['required', 'date_format:Y-m-d', 'after:fechaInicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreTorneo.required'        => 'El nombre del torneo es obligatorio.',
            'tipoTorneo.required'          => 'Debes seleccionar el tipo de torneo.',
            'tipoTorneo.in'                => 'El tipo de torneo no es válido.',
            'modalidadPago.required'       => 'Debes seleccionar la modalidad de pago.',
            'modalidadPago.in'             => 'La modalidad de pago no es válida.',
            'organizadorNombre.required'   => 'El nombre del organizador es obligatorio.',
            'organizadorEmail.email'       => 'El correo del organizador no es válido.',
            'temporada.required'           => 'La temporada es obligatoria.',
            'temporada.integer'            => 'La temporada debe ser un año válido.',
            'fechaInicio.required'         => 'La fecha de inicio es obligatoria.',
            'fechaInicio.date_format'      => 'La fecha de inicio debe tener formato YYYY-MM-DD.',
            'fechaFin.required'            => 'La fecha de fin es obligatoria.',
            'fechaFin.date_format'         => 'La fecha de fin debe tener formato YYYY-MM-DD.',
            'fechaFin.after'               => 'La fecha de fin debe ser posterior al inicio.',
        ];
    }
}
