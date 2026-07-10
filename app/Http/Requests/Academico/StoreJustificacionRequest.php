<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use Illuminate\Foundation\Http\FormRequest;

class StoreJustificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo'       => ['required', 'string'],
            'documentoPdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required'     => 'El motivo de la justificación es obligatorio.',
            'documentoPdf.mimes'  => 'El documento debe ser un PDF.',
            'documentoPdf.max'    => 'El PDF no puede superar 5 MB.',
        ];
    }
}
