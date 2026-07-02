<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use App\Models\DivisionTorneo;

class UpdateDivisionRequest extends DivisionRequest
{
    public function rules(): array
    {
        $division = DivisionTorneo::findOrFail((int) $this->route('id'));

        return [
            'nombreDivision' => $this->reglasNombre($division->idTorneo, $division->idDivision),
            'descripcion'    => ['nullable', 'string'],
        ];
    }
}
