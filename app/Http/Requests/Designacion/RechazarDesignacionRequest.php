<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class RechazarDesignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:300'],
        ];
    }
}
