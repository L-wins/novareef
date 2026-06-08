<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class UpdateColegio extends ColegioRequest
{
    public function rules(): array
    {
        // El ID del colegio viene en la ruta → se excluye del unique check de código.
        $idColegio = (int) $this->route('id');

        return $this->reglasComunes($idColegio);
    }
}
