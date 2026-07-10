<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use App\Models\AsistenciaAcademica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorregirMarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estadoAsistencia' => ['required', Rule::in([
                AsistenciaAcademica::ESTADO_PRESENTE,
                AsistenciaAcademica::ESTADO_AUSENTE,
            ])],
        ];
    }
}
