<?php

declare(strict_types=1);

namespace App\Http\Requests\Finanza;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaldoInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto es obligatorio.',
            'monto.min'      => 'El monto debe ser mayor a cero.',
            'fecha.required' => 'La fecha es obligatoria.',
        ];
    }
}
