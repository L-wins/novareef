<?php

declare(strict_types=1);

namespace App\Http\Requests\Arbitro;

class StoreArbitroRequest extends ArbitroRequest
{
    public function rules(): array
    {
        return [
            'nombreUsuario' => ['required', 'string', 'max:150'],
            'emailUsuario' => ['required', 'email', 'max:255', 'unique:usuarios,emailUsuario'],
            'telefonoUsuario' => ['required', 'string', 'max:20'],
            'idCategoria' => ['required', 'integer', $this->reglaCategoriaAsignable()],
            'tipoDocumento' => ['required', 'in:cedula,pasaporte,extranjeria'],
            'numeroDocumento' => ['required', 'string', 'max:30'],
            'fechaIngresoColegio' => ['required', 'date'],
            'lugarExpedicionCC' => ['nullable', 'string', 'max:100'],
        ];
    }
}
