<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SolicitudArco;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudArcoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'    => ['required', 'string', Rule::in(SolicitudArco::TIPOS)],
            'mensaje' => ['required', 'string', 'max:2000'],
        ];
    }
}
