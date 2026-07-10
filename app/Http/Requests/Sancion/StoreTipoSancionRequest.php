<?php

declare(strict_types=1);

namespace App\Http\Requests\Sancion;

use App\Models\TipoSancion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTipoSancionRequest extends FormRequest
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
                Rule::unique('tipos_sancion', 'nombre')->where('idColegio', $idColegio),
            ],
            'etiqueta'  => ['required', 'string', 'max:80'],
            'severidad' => ['required', Rule::in([
                TipoSancion::SEVERIDAD_LEVE,
                TipoSancion::SEVERIDAD_MODERADA,
                TipoSancion::SEVERIDAD_GRAVE,
            ])],
            'diasSuspensionSugeridos' => ['nullable', 'integer', 'min:0'],
            'descripcion'             => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre del tipo de sanción es obligatorio.',
            'nombre.unique'     => 'Ya existe un tipo de sanción con ese nombre en este colegio.',
            'etiqueta.required' => 'La etiqueta visible es obligatoria.',
            'severidad.required' => 'Selecciona la severidad.',
        ];
    }
}
