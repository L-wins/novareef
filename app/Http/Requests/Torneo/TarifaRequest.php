<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas compartidas para store y update de tarifa.
 * store usa idRol + idFormato; update solo valorPago — se extiende si se necesita.
 */
class TarifaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idRol'     => ['required', 'exists:roles_partido,idRol'],
            'idFormato' => ['required', 'exists:formatos_designacion,idFormato'],
            'valorPago' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'idRol.required'     => 'Debes seleccionar un rol.',
            'idRol.exists'       => 'El rol seleccionado no es válido.',
            'idFormato.required' => 'Debes seleccionar un formato de designación.',
            'idFormato.exists'   => 'El formato seleccionado no es válido.',
            'valorPago.required' => 'El valor del pago es obligatorio.',
            'valorPago.numeric'  => 'El valor del pago debe ser un número.',
            'valorPago.min'      => 'El valor del pago no puede ser negativo.',
        ];
    }
}
