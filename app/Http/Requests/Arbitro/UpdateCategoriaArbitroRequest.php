<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCategoriaArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $descripcion = $this->input('descripcion');

        $this->merge([
            'nombreCategoria' => trim((string) $this->input('nombreCategoria')),
            'descripcion' => $descripcion === null ? null : trim((string) $descripcion),
        ]);
    }

    public function rules(): array
    {
        $idColegio = (int) Auth::user()->idColegio;
        $idCategoria = (int) $this->route('id');

        return [
            'nombreCategoria' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categorias_arbitro', 'nombreCategoria')
                    ->where('idColegio', $idColegio)
                    ->ignore($idCategoria, 'idCategoria'),
            ],
            'descripcion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreCategoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombreCategoria.max' => 'El nombre no puede superar 50 caracteres.',
            'nombreCategoria.unique' => 'Ya existe una categoría con ese nombre en este colegio.',
            'descripcion.max' => 'La descripción no puede superar 100 caracteres.',
        ];
    }
}
