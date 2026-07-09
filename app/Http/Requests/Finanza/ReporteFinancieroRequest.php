<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use Illuminate\Foundation\Http\FormRequest;

class ReporteFinancieroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Nullable — sin parámetros, el controller aplica el rango por
        // defecto (mes actual); no hay período fijo obligatorio.
        return [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ];
    }

    public function messages(): array
    {
        return [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
