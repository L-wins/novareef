<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\MovimientoFinanciero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCargoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nómina/externo quedan fuera a propósito — esos son automáticos
            // (finalización de partido) o se pagan vía pago acumulado, nunca
            // un cargo suelto creado a mano.
            'categoria' => ['required', Rule::in([
                MovimientoFinanciero::CATEGORIA_MULTA,
                MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
                MovimientoFinanciero::CATEGORIA_OTRO_INGRESO,
            ])],
            'concepto'        => ['required', 'string', 'max:255'],
            'montoTotal'      => ['required', 'numeric', 'min:0.01'],
            'fechaMovimiento' => ['required', 'date'],
            'observaciones'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required'        => 'Selecciona una categoría.',
            'concepto.required'         => 'El concepto es obligatorio.',
            'montoTotal.required'       => 'El monto es obligatorio.',
            'montoTotal.min'            => 'El monto debe ser mayor a cero.',
            'fechaMovimiento.required'  => 'La fecha es obligatoria.',
        ];
    }
}
