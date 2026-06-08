<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas compartidas para store y update de sede — idénticas en ambos casos.
 */
class SedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombreSede'    => ['required', 'string', 'max:150'],
            'direccion'     => ['required', 'string', 'max:255'],
            'municipio'     => ['required', 'string', 'max:100'],
            'departamento'  => ['nullable', 'string', 'max:100'],
            'urlMaps'       => ['nullable', 'url', 'max:500'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreSede.required' => 'El nombre de la sede es obligatorio.',
            'direccion.required'  => 'La dirección es obligatoria.',
            'municipio.required'  => 'El municipio es obligatorio.',
            'urlMaps.url'         => 'La URL de Google Maps no es válida.',
        ];
    }
}
