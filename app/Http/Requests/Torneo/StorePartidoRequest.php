<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use App\Models\Torneo;

class StorePartidoRequest extends PartidoRequest
{
    public function rules(): array
    {
        $torneo = Torneo::findOrFail((int) $this->route('torneoId'));

        return $this->reglas($torneo->idTorneo);
    }
}
