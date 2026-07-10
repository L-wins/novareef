<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fechaAbono' => ['required', 'date'],
            'metodoPago' => ['required', Rule::in([
                AbonoMovimiento::METODO_EFECTIVO,
                AbonoMovimiento::METODO_TRANSFERENCIA,
                AbonoMovimiento::METODO_CONSIGNACION,
                AbonoMovimiento::METODO_OTRO,
            ])],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required'      => 'El monto del abono es obligatorio.',
            'monto.min'           => 'El monto debe ser mayor a cero.',
            'fechaAbono.required' => 'La fecha del abono es obligatoria.',
            'metodoPago.required' => 'Selecciona el método de pago.',
        ];
    }
}
