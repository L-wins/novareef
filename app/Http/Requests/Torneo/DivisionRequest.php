<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base para store y update de divisiones.
 * La unicidad del nombre varía entre crear (solo torneo) y actualizar (ignora el propio).
 */
abstract class DivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function reglasNombre(int $idTorneo, ?int $ignoreId = null): array
    {
        $unique = Rule::unique('divisiones_torneo', 'nombreDivision')->where('idTorneo', $idTorneo);

        if ($ignoreId !== null) {
            $unique = $unique->ignore($ignoreId, 'idDivision');
        }

        return ['required', 'string', 'max:100', $unique];
    }

    public function messages(): array
    {
        return [
            'nombreDivision.required' => 'El nombre de la división es obligatorio.',
            'nombreDivision.unique'   => 'Ya existe una división con ese nombre en este torneo.',
        ];
    }
}
