<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaldoInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto'         => ['required', 'numeric', 'min:0.01'],
            'fecha'         => ['required', 'date'],
            'metodoPago'    => ['required', Rule::in(AbonoMovimiento::METODOS_MANUALES)],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required'      => 'El monto es obligatorio.',
            'monto.min'           => 'El monto debe ser mayor a cero.',
            'fecha.required'      => 'La fecha es obligatoria.',
            'metodoPago.required' => 'Selecciona el método de pago.',
        ];
    }
}
