<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class MarcarNoDisponibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // La fecha viene en la URL — la validamos aquí para proteger Carbon::parse().
            'fecha'  => ['required', 'date_format:Y-m-d'],
            'motivo' => ['required', 'string', 'max:300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Inyecta el parámetro de ruta en el bag de validación.
        $this->merge(['fecha' => $this->route('fecha')]);
    }

    public function messages(): array
    {
        return [
            'fecha.required'    => 'La fecha es obligatoria.',
            'fecha.date_format' => 'Formato de fecha inválido.',
            'motivo.required'   => 'Debes indicar el motivo de la no disponibilidad.',
            'motivo.max'        => 'El motivo no puede superar los 300 caracteres.',
        ];
    }
}
