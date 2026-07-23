<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use App\Services\DocumentoArbitroService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequisitoDocumentoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idRequisito = $this->route('idRequisito');
        $idColegio = $this->user()?->idColegio;

        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('requisitos_documento_arbitro', 'nombre')
                    ->where('idColegio', $idColegio)
                    ->ignore($idRequisito, 'idRequisito'),
            ],
            'idCategoria' => [
                'nullable',
                'integer',
                Rule::exists('categorias_arbitro', 'idCategoria')
                    ->where('idColegio', $idColegio)
                    ->where('activa', true),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'obligatorio' => ['sometimes', 'boolean'],
            'requiereRevision' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
            'plantilla' => [
                'nullable',
                'file',
                'mimes:'.DocumentoArbitroService::MIME_RULE,
                'max:'.DocumentoArbitroService::MAX_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del documento solicitado.',
            'nombre.unique' => 'Ya existe un requisito documental con ese nombre.',
            'idCategoria.exists' => 'La categoria seleccionada no pertenece al colegio o no esta activa.',
            'plantilla.mimes' => 'La plantilla debe ser PDF, Word o imagen JPG/PNG.',
            'plantilla.max' => 'La plantilla no puede superar 10 MB.',
        ];
    }
}
