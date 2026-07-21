<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\AbonoMovimiento;
use App\Models\MovimientoFinanciero;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreMovimientoInstitucionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipoMovimiento'  => ['required', Rule::in([MovimientoFinanciero::TIPO_INGRESO, MovimientoFinanciero::TIPO_EGRESO])],
            'categoria'       => ['required', Rule::in(MovimientoFinanciero::CATEGORIAS_INSTITUCIONALES)],
            'concepto'        => ['required', 'string', 'max:255'],
            'montoTotal'      => ['required', 'numeric', 'min:0.01'],
            'fechaMovimiento' => ['required', 'date'],
            'idTorneo'        => [
                'nullable', 'integer',
                Rule::exists('torneos', 'idTorneo')->where('idColegio', Auth::user()?->idColegio),
            ],
            // El movimiento nace ya pagado (ver FinanzasService::registrarMovimientoPagado) —
            // si se está registrando es porque el dinero ya se movió, así que
            // el método de pago se captura de una vez, no en un paso aparte.
            'metodoPago'      => ['required', Rule::in(AbonoMovimiento::METODOS_MANUALES)],
            'observaciones'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipoMovimiento.required' => 'Selecciona el tipo de movimiento.',
            'categoria.required'      => 'Selecciona una categoría.',
            'categoria.in'            => 'Esa categoría no corresponde al tipo de movimiento seleccionado.',
            'concepto.required'       => 'El concepto es obligatorio.',
            'montoTotal.required'     => 'El monto es obligatorio.',
            'montoTotal.min'          => 'El monto debe ser mayor a cero.',
            'fechaMovimiento.required' => 'La fecha es obligatoria.',
            'idTorneo.exists'         => 'El torneo seleccionado no pertenece a tu colegio.',
            'metodoPago.required'     => 'Selecciona el método de pago.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Rule::in ya valida que la categoría exista en el set institucional,
        // pero no que coincida con el tipoMovimiento elegido (ingreso vs
        // egreso) — sin esto se podría mandar "gasto_fijo" con tipo
        // "ingreso" y el error solo aparecería como RuntimeException del
        // Service, ya tarde para mostrarlo junto al campo.
        $validator->after(function (Validator $validator): void {
            $tipo      = $this->input('tipoMovimiento');
            $categoria = $this->input('categoria');

            if (! $tipo || ! $categoria) {
                return;
            }

            $categoriasValidas = MovimientoFinanciero::CATEGORIAS_POR_TIPO[$tipo] ?? [];
            if (! in_array($categoria, $categoriasValidas, true)) {
                $validator->errors()->add('categoria', 'Esa categoría no corresponde al tipo de movimiento seleccionado.');
            }
        });
    }
}
