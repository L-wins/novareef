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
        // Sin 'svg': es XML y puede embeber <script>/manejadores de evento — se
        // guarda en disco público y se sirve por URL directa, así que un SVG
        // malicioso "como foto" sería XSS almacenado contra quien abra el link.
        return [
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp,bmp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una imagen.',
            'foto.image'    => 'El archivo debe ser una imagen.',
            'foto.mimes'    => 'Formatos permitidos: jpg, jpeg, png, gif, webp, bmp.',
            'foto.max'      => 'La imagen no puede superar 5 MB.',
        ];
    }
}
