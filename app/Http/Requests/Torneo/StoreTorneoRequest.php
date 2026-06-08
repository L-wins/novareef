<?php

declare(strict_types=1);

namespace App\Http\Requests\Torneo;

class StoreTorneoRequest extends TorneoRequest
{
    public function rules(): array
    {
        return array_merge($this->reglasBase(), [
            'modalidadPago' => ['required', 'in:campo,nomina'],
        ]);
    }
}
