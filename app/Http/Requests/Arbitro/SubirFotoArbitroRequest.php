<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

use Illuminate\Foundation\Http\FormRequest;

class SubirFotoArbitroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una imagen.',
            'foto.image'    => 'El archivo debe ser una imagen.',
            'foto.mimes'    => 'Formatos permitidos: jpg, jpeg, png, gif, webp, bmp, svg.',
            'foto.max'      => 'La imagen no puede superar 5 MB.',
        ];
    }
}
