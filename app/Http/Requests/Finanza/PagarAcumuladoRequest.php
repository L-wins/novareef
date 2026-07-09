<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagarAcumuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idArbitro' => ['required', 'integer', 'exists:arbitros,idArbitro'],
            'idsMovimientosNomina'   => ['required', 'array', 'min:1'],
            'idsMovimientosNomina.*' => ['integer', 'exists:movimientos_financieros,idMovimiento'],
            'idsDeudasNetear'        => ['nullable', 'array'],
            'idsDeudasNetear.*'      => ['integer', 'exists:movimientos_financieros,idMovimiento'],
            'fecha'      => ['required', 'date'],
            'metodoPago' => ['required', Rule::in([
                AbonoMovimiento::METODO_EFECTIVO,
                AbonoMovimiento::METODO_TRANSFERENCIA,
                AbonoMovimiento::METODO_CONSIGNACION,
                AbonoMovimiento::METODO_OTRO,
            ])],
            'referencia' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'idArbitro.required'             => 'Selecciona un árbitro.',
            'idsMovimientosNomina.required'   => 'Selecciona al menos un pago de nómina pendiente.',
            'idsMovimientosNomina.min'        => 'Selecciona al menos un pago de nómina pendiente.',
            'fecha.required'                  => 'La fecha del pago es obligatoria.',
            'metodoPago.required'             => 'Selecciona el método de pago.',
        ];
    }
}
