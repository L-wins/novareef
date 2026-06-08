<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCategoriaArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idColegio = (int) Auth::user()->idColegio;

        return [
            'nombreCategoria' => [
                'required',
                'string',
                'max:50',
                // Unicidad scoped al colegio — sin closure, expresado declarativamente.
                Rule::unique('categorias_arbitro', 'nombreCategoria')
                    ->where('idColegio', $idColegio),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreCategoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombreCategoria.max'      => 'El nombre no puede superar 50 caracteres.',
            'nombreCategoria.unique'   => 'Ya existe una categoría con ese nombre en este colegio.',
        ];
    }
}
