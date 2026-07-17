<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use App\Models\Partido;
use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoPartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estadoNuevo' => ['required', 'string', 'in:' . implode(',', [
                Partido::ESTADO_PROGRAMADO,
                Partido::ESTADO_APLAZADO,
                Partido::ESTADO_FINALIZADO,
                Partido::ESTADO_CANCELADO,
            ])],
            'detalle' => ['nullable', 'string', 'max:500'],
        ];
    }
}
