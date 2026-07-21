<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagarNominaArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idsMovimientos'   => ['required', 'array', 'min:1'],
            'idsMovimientos.*' => ['integer', 'exists:movimientos_financieros,idMovimiento'],
            'fecha'      => ['required', 'date'],
            'metodoPago' => ['required', Rule::in(AbonoMovimiento::METODOS_MANUALES)],
        ];
    }

    public function messages(): array
    {
        return [
            'idsMovimientos.required' => 'Selecciona al menos un partido a pagar.',
            'fecha.required'          => 'La fecha es obligatoria.',
            'metodoPago.required'     => 'Selecciona el método de pago.',
        ];
    }
}
