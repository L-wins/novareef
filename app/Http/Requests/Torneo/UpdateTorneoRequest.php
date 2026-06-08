<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

use App\Models\Torneo;

class UpdateTorneoRequest extends TorneoRequest
{
    public function rules(): array
    {
        $torneo = Torneo::withCount('partidos')->findOrFail((int) $this->route('id'));

        $reglas = $this->reglasBase();

        // modalidadPago es inmutable cuando el torneo ya tiene partidos registrados.
        if ($torneo->partidos_count === 0) {
            $reglas['modalidadPago'] = ['required', 'in:campo,nomina'];
        }

        return $reglas;
    }
}
