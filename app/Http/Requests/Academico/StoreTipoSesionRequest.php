<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTipoSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idColegio = (int) Auth::user()->idColegio;

        return [
            'etiqueta' => [
                'required', 'string', 'max:80',
                Rule::unique('tipos_sesion_academica', 'etiqueta')->where('idColegio', $idColegio),
            ],
            'esOficial'   => ['sometimes', 'boolean'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'etiqueta.required' => 'El nombre del tipo de sesión es obligatorio.',
            'etiqueta.unique'   => 'Ya existe un tipo de sesión con ese nombre en este colegio.',
        ];
    }
}
