<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;

class DevolverDocumentoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentarioRevision' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comentarioRevision.required' => 'Indica que debe corregir el arbitro.',
            'comentarioRevision.min' => 'El comentario debe tener al menos 5 caracteres.',
            'comentarioRevision.max' => 'El comentario no puede superar 1000 caracteres.',
        ];
    }
}
