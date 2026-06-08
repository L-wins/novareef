<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

/** Update solo modifica el valor — rol y formato son inmutables después de crear. */
class UpdateTarifaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valorPago' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'valorPago.required' => 'El valor del pago es obligatorio.',
            'valorPago.numeric'  => 'El valor del pago debe ser un número.',
            'valorPago.min'      => 'El valor del pago no puede ser negativo.',
        ];
    }
}
