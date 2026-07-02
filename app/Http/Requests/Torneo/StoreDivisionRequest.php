<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

class StoreDivisionRequest extends DivisionRequest
{
    public function rules(): array
    {
        $idTorneo = (int) $this->route('torneoId');

        return [
            'nombreDivision' => $this->reglasNombre($idTorneo),
            'descripcion'    => ['nullable', 'string'],
        ];
    }
}
