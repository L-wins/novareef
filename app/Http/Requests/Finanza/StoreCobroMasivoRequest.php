<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use App\Models\MovimientoFinanciero;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCobroMasivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria' => ['required', Rule::in([
                MovimientoFinanciero::CATEGORIA_MENSUALIDAD,
                MovimientoFinanciero::CATEGORIA_OTRO_INGRESO,
            ])],
            'concepto'        => ['required', 'string', 'max:255'],
            'fechaMovimiento' => ['required', 'date'],
            'montoTotal'      => ['required', 'numeric', 'min:0.01'],
            'observaciones'   => ['nullable', 'string'],

            'cargos'               => ['required', 'array', 'min:1'],
            'cargos.*.idArbitro'   => ['required', 'integer', 'distinct', 'exists:arbitros,idArbitro'],
            'cargos.*.incluir'     => ['nullable', 'boolean'],
            'cargos.*.monto'       => ['nullable', 'numeric', 'min:0.01'],
            'cargos.*.yaPago'      => ['nullable', 'boolean'],
            'cargos.*.metodoPago'  => ['nullable', Rule::in(AbonoMovimiento::METODOS_MANUALES)],
            'cargos.*.fechaAbono' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required'        => 'Selecciona una categoría.',
            'concepto.required'         => 'El concepto es obligatorio.',
            'fechaMovimiento.required'  => 'La fecha es obligatoria.',
            'montoTotal.required'       => 'El monto por defecto es obligatorio.',
            'montoTotal.min'            => 'El monto debe ser mayor a cero.',
            'cargos.required'           => 'Selecciona al menos un árbitro.',
            'cargos.min'                => 'Selecciona al menos un árbitro.',
        ];
    }

    /** Exige método de pago y fecha de abono solo en las filas marcadas "ya pagó". */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('cargos', []) as $i => $cargo) {
                if (empty($cargo['incluir']) || empty($cargo['yaPago'])) {
                    continue;
                }

                if (empty($cargo['metodoPago'])) {
                    $validator->errors()->add("cargos.$i.metodoPago", 'Selecciona el método de pago.');
                }
                if (empty($cargo['fechaAbono'])) {
                    $validator->errors()->add("cargos.$i.fechaAbono", 'La fecha de pago es obligatoria.');
                }
            }
        });
    }
}
