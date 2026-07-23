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

    /**
     * El formulario expone un único select "Aplica a" (alcanceRequisito) con
     * valores 'categoria:{id}' / 'arbitro:{id}' / vacío ('todos') — así el
     * alcance es mutuamente excluyente por construcción, sin necesitar una
     * regla de validación aparte que lo verifique. Aquí se traduce a las
     * columnas reales idCategoria/idArbitro que el resto del código espera.
     */
    protected function prepareForValidation(): void
    {
        $alcance = (string) $this->input('alcanceRequisito', '');
        $idCategoria = null;
        $idArbitro = null;

        if (str_starts_with($alcance, 'categoria:')) {
            $idCategoria = substr($alcance, strlen('categoria:'));
        } elseif (str_starts_with($alcance, 'arbitro:')) {
            $idArbitro = substr($alcance, strlen('arbitro:'));
        }

        $this->merge(['idCategoria' => $idCategoria, 'idArbitro' => $idArbitro]);
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
            'idArbitro' => [
                'nullable',
                'integer',
                Rule::exists('arbitros', 'idArbitro')
                    ->where('idColegio', $idColegio)
                    ->where('deleted_at', null),
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
            'idCategoria.exists' => 'La categoría seleccionada no pertenece al colegio o no está activa.',
            'idArbitro.exists' => 'El árbitro seleccionado no pertenece al colegio.',
            'plantilla.mimes' => 'La plantilla debe ser PDF, Word o imagen JPG/PNG.',
            'plantilla.max' => 'La plantilla no puede superar 10 MB.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $idRequisito = $this->route('idRequisito');

        if ($idRequisito) {
            return route('requisitos-documentos-arbitro.index', ['abrir' => $idRequisito]).'#requisito-'.$idRequisito;
        }

        return route('requisitos-documentos-arbitro.index').'#crear-requisito';
    }
}
