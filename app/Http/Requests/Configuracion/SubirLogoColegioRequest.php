<?php

declare(strict_types=1);

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class SubirLogoColegioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización (permission:editar-arbitros) la cubre el middleware de la ruta.
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Debes seleccionar una imagen.',
            'logo.image'    => 'El archivo debe ser una imagen.',
            'logo.mimes'    => 'Formatos permitidos: jpg, jpeg, png, webp.',
            'logo.max'      => 'El logo no puede superar 2 MB.',
        ];
    }
}
