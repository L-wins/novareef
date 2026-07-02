<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

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
            'estadoNuevo'        => ['required', 'in:programado,en_curso,finalizado,aplazado,cancelado'],
            'resultadoLocal'     => ['nullable', 'integer', 'min:0', 'required_if:estadoNuevo,finalizado'],
            'resultadoVisitante' => ['nullable', 'integer', 'min:0', 'required_if:estadoNuevo,finalizado'],
        ];
    }

    public function messages(): array
    {
        return [
            'estadoNuevo.required'           => 'Debes seleccionar un nuevo estado.',
            'estadoNuevo.in'                 => 'El estado seleccionado no es válido.',
            'resultadoLocal.required_if'     => 'El resultado del local es obligatorio para finalizar.',
            'resultadoVisitante.required_if' => 'El resultado del visitante es obligatorio para finalizar.',
        ];
    }
}
