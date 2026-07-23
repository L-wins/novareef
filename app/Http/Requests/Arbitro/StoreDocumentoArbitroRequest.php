<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use App\Services\DocumentoArbitroService;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:'.DocumentoArbitroService::MIME_RULE,
                'max:'.DocumentoArbitroService::MAX_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona el documento que vas a entregar.',
            'archivo.mimes' => 'Formatos permitidos: PDF, Word, JPG o PNG.',
            'archivo.max' => 'El documento no puede superar 10 MB.',
        ];
    }
}
