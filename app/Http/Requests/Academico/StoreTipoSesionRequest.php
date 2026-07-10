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
            'nombre' => [
                'required', 'string', 'max:60',
                Rule::unique('tipos_sesion_academica', 'nombre')->where('idColegio', $idColegio),
            ],
            'etiqueta'    => ['required', 'string', 'max:80'],
            'esOficial'   => ['sometimes', 'boolean'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre del tipo de sesión es obligatorio.',
            'nombre.unique'     => 'Ya existe un tipo de sesión con ese nombre en este colegio.',
            'etiqueta.required' => 'La etiqueta visible es obligatoria.',
        ];
    }
}
