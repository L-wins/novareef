<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nota'       => ['required', 'numeric', 'in:1,1.5,2,2.5,3,3.5,4,4.5,5'],
            'comentario' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
