<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialAcademicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'  => ['required', 'string', 'max:150'],
            'archivo' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required'  => 'Dale un título al material.',
            'archivo.required' => 'Selecciona un archivo.',
            'archivo.mimes'    => 'Formatos permitidos: PDF, Word, PowerPoint, Excel o imagen.',
            'archivo.max'      => 'El archivo no puede superar 20 MB.',
        ];
    }
}
