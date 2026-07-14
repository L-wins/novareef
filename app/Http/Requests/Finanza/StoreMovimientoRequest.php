<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use App\Models\MovimientoFinanciero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipoMovimiento' => ['required', Rule::in([
                MovimientoFinanciero::TIPO_INGRESO,
                MovimientoFinanciero::TIPO_EGRESO,
            ])],
            // saldo_inicial queda excluido: solo se crea vía
            // FinanzasService::registrarSaldoInicial() (con abono automático),
            // nunca desde este formulario genérico.
            'categoria' => ['required', 'string', Rule::notIn([MovimientoFinanciero::CATEGORIA_SALDO_INICIAL])],
            'concepto' => ['required', 'string', 'max:255'],
            'montoTotal' => ['required', 'numeric', 'min:0.01'],
            'fechaMovimiento' => ['required', 'date'],
            'idArbitro' => ['nullable', 'integer', 'exists:arbitros,idArbitro'],
            'nombreArbitroExterno' => ['nullable', 'string', 'max:150'],
            'documentoArbitroExterno' => ['nullable', 'string', 'max:30'],
            'idTorneo' => ['nullable', 'integer', 'exists:torneos,idTorneo'],
            'idPartido' => ['nullable', 'integer', 'exists:partidos,idPartido'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required'     => 'Selecciona una categoría.',
            'concepto.required'      => 'El concepto es obligatorio.',
            'montoTotal.required'    => 'El monto es obligatorio.',
            'montoTotal.min'        => 'El monto debe ser mayor a cero.',
            'fechaMovimiento.required' => 'La fecha es obligatoria.',
        ];
    }
}
