<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use App\Models\Partido;

class UpdatePartidoRequest extends PartidoRequest
{
    public function rules(): array
    {
        $partido = Partido::findOrFail((int) $this->route('id'));

        return $this->reglas($partido->idTorneo);
    }
}
