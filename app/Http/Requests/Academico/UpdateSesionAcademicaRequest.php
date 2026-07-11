<?php

declare(strict_types=1);

namespace App\Http\Requests\Academico;

use App\Models\SesionAcademica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Edición de una sesión que aún no se ha abierto — a diferencia del alta,
 * no permite cambiar `dirigidaA`/`idCategoria`: los registros de asistencia
 * ya se generaron para el criterio original al crearla (ver
 * SesionAcademicaService::actualizarSesion).
 */
class UpdateSesionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Los checkboxes HTML no envían nada si quedan desmarcados — sin esto,
     * "esObligatoria" jamás llegaría en false. $this->boolean() trata la
     * ausencia como false.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['esObligatoria' => $this->boolean('esObligatoria')]);
    }

    public function rules(): array
    {
        $idColegio = (int) Auth::user()->idColegio;

        return [
            'idTipoSesion' => [
                'required', 'integer',
                Rule::exists('tipos_sesion_academica', 'idTipoSesion')->where('idColegio', $idColegio),
            ],
            'modalidad'   => ['required', Rule::in([SesionAcademica::MODALIDAD_PRESENCIAL, SesionAcademica::MODALIDAD_VIRTUAL])],
            'urlVirtual'  => ['nullable', 'url', 'max:255', 'required_if:modalidad,' . SesionAcademica::MODALIDAD_VIRTUAL],
            'tema'        => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'lugar'       => ['nullable', 'string', 'max:150'],
            'fechaSesion' => ['required', 'date'],
            'horaSesion'  => ['required', 'date_format:H:i'],
            'duracionMinutos' => ['required', 'integer', 'min:1'],
            'modoAsistencia'  => ['required', Rule::in([SesionAcademica::MODO_MANUAL, SesionAcademica::MODO_SCANNER])],
            'esObligatoria'   => ['boolean'],
        ];
    }
}
