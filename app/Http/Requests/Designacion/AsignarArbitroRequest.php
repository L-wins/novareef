<?php

declare(strict_types=1);

namespace App\Http\Requests\Designacion;

use Illuminate\Foundation\Http\FormRequest;

class AsignarArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idArbitro' => ['required', 'integer'],
            'idRol'     => ['required', 'integer'],
        ];
    }
}
